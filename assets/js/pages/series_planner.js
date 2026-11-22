$(function(){'use strict';
const el=document.getElementById('series-planner-modal');if(!el)return;
const modal=bootstrap.Modal.getOrCreateInstance(el),weekdays=['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
const $msg=$('#series-planner-message'),$cats=$('#series-category-cards'),$service=$('#series-service'),$provider=$('#series-provider'),$employees=$('#series-employees'),$slots=$('#series-slots'),$preview=$('#series-preview-list'),$save=$('#series-create');
let catalog=null,category=null,editingSeriesId=null;
function message(text,type='danger'){$msg.removeClass('d-none alert-danger alert-success alert-warning').addClass('alert-'+type).text(text)}
function clear(){ $msg.addClass('d-none').text(''); }
function normalizeSeriesTime(value) {
    const input = String(value || '').trim();

    if (!input) {
        return '';
    }

    let hours;
    let minutes;

    if (/^\d{3,4}$/.test(input)) {
        const digits = input.padStart(4, '0');
        hours = Number(digits.slice(0, 2));
        minutes = Number(digits.slice(2, 4));
    } else {
        const match = input.match(/^(\d{1,2}):(\d{2})$/);

        if (!match) {
            return null;
        }

        hours = Number(match[1]);
        minutes = Number(match[2]);
    }

    if (hours > 23 || minutes > 59) {
        return null;
    }

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

function slotRow(v = {weekday: '', start_time: '', duration_minutes: 60}) {
    const $row = $('<div/>', {
        'class': 'series-slot-row row g-2 align-items-end mb-3',
    });

    const $weekday = $('<select/>', {
        'class': 'form-select slot-weekday',
    });

    $weekday.append(
        $('<option/>', {
            value: '',
            text: 'Wochentag wählen',
        }),
    );

    weekdays.forEach((name, index) => {
        $weekday.append(
            $('<option/>', {
                value: index,
                text: name,
            }),
        );
    });

    if (v.weekday !== '' && v.weekday !== null && v.weekday !== undefined) {
        $weekday.val(String(v.weekday));
    }

    const $time = $('<input/>', {
        type: 'text',
        inputmode: 'numeric',
        maxlength: 5,
        autocomplete: 'off',
        placeholder: 'z. B. 1500',
        'class': 'form-control slot-time',
        value: String(v.start_time || '').slice(0, 5),
    });

    $time.on('blur', () => {
        const normalized = normalizeSeriesTime($time.val());

        $time.toggleClass('is-invalid', normalized === null);

        if (normalized !== null) {
            $time.val(normalized);
        }
    });

    const $duration = $('<input/>', {
        type: 'number',
        min: 1,
        'class': 'form-control slot-duration',
        value: v.duration_minutes || 60,
    });

    const field = (label, input, className) =>
        $('<div/>', {
            'class': className,
            'html': [
                $('<label/>', {
                    'class': 'form-label mb-1',
                    text: label,
                }),
                input,
            ],
        });

    const $remove = $('<button/>', {
        type: 'button',
        'class': 'btn btn-outline-danger slot-remove',
        title: 'Terminzeit entfernen',
        'aria-label': 'Terminzeit entfernen',
        'html': $('<i/>', {
            'class': 'fas fa-trash',
        }),
    });

    $row.append(
        field('Wochentag', $weekday, 'col-12 col-md-4'),
        field('Startzeit', $time, 'col-12 col-md-3'),
        field('Dauer (Minuten)', $duration, 'col-12 col-md-3'),
        $('<div/>', {
            'class': 'col-12 col-md-2',
            'html': $remove,
        }),
    );

    $slots.append($row);
}

function loadCatalog(){return catalog?$.Deferred().resolve(catalog).promise():$.get(App.Utils.Url.siteUrl('series_planner/catalog')).then(d=>{catalog=d;return d})}
function drawCategories(){ $cats.empty();catalog.categories.forEach(c=>$('<button/>',{type:'button',class:'series-category-card','data-id':c.id,text:c.name}).appendTo($cats)); }
function chooseCategory(id){category=Number(id);$('.series-category-card').removeClass('active');$('.series-category-card[data-id="'+id+'"]').addClass('active');$service.empty().append('<option value="">Dienstleistung wählen</option>');catalog.services.filter(s=>Number(s.id_service_categories)===category).forEach(s=>$('<option/>',{value:s.id,text:s.name}).appendTo($service));$service.prop('disabled',false)}
function renderEmployees(selectedIds = []) {
    const serviceId = Number($service.val());
    const initiatorId = Number($provider.val());

    const assignedIds = catalog.assignments
        .filter(assignment => Number(assignment.id_services) === serviceId)
        .map(assignment => Number(assignment.id_users));

    const employees = catalog.providers.filter(provider =>
        assignedIds.includes(Number(provider.id)) &&
        Number(provider.id) !== initiatorId
    );

    $employees.empty();

    if (!employees.length) {
        $employees.append(
            $('<span/>', {
                'class': 'text-muted',
                'text': initiatorId
                    ? 'Keine weiteren Mitarbeiter verfügbar.'
                    : 'Zuerst einen Initiator auswählen.',
            }),
        );

        return;
    }

    employees.forEach(employee => {
        const id = Number(employee.id);
        const name = [employee.first_name, employee.last_name]
            .filter(Boolean)
            .join(' ');

        const checkboxId = `series-employee-${id}`;

        const $checkbox = $('<input/>', {
            'type': 'checkbox',
            'class': 'form-check-input series-employee',
            'id': checkboxId,
            'value': id,
            'checked': selectedIds.map(Number).includes(id),
        });

        const $label = $('<label/>', {
            'class': 'form-check-label',
            'for': checkboxId,
            'text': name,
        });

        $('<div/>', {
            'class': 'form-check mb-2',
            'html': [$checkbox, $label],
        }).appendTo($employees);
    });
}

function chooseService(selectedEmployeeIds = []) {
    const service = catalog.services.find(
        item => Number(item.id) === Number($service.val())
    );

    if (!service) {
        $employees
            .empty()
            .append(
                $('<span/>', {
                    'class': 'text-muted',
                    'text': 'Zuerst eine Dienstleistung auswählen.',
                }),
            );

        return;
    }

    $('#series-capacity').val(service.attendants_number || 1);
    $('#series-location').val(service.location || '');
    $('.slot-duration').val(service.duration || 60);

    const ids = catalog.assignments
        .filter(assignment =>
            Number(assignment.id_services) === Number(service.id)
        )
        .map(assignment => Number(assignment.id_users));

    $provider
        .empty()
        .append('<option value="">Initiator wählen</option>');

    catalog.providers
        .filter(provider => ids.includes(Number(provider.id)))
        .forEach(provider =>
            $('<option/>', {
                'value': provider.id,
                'text': [provider.first_name, provider.last_name]
                    .filter(Boolean)
                    .join(' '),
            }).appendTo($provider)
        );

    $provider.prop('disabled', false);

    if (ids.length === 1) {
        $provider.val(String(ids[0]));
    }

    renderEmployees(selectedEmployeeIds);
}
function syncPrimarySlot(){
    const startDate = $('#series-start-date').val();

    if (!startDate) {
        return;
    }

    const date = moment(startDate, 'YYYY-MM-DD', true);

    if (!date.isValid()) {
        return;
    }

    const weekday = date.day();

    let $primary = $slots.find('.series-slot-row').first();

    if (!$primary.length) {
        const service = catalog?.services?.find(
            x => Number(x.id) === Number($service.val())
        );

        slotRow({
            weekday,
            start_time: '',
            duration_minutes: Number(service?.duration) || 60,
        });

        $primary = $slots.find('.series-slot-row').first();
    }

    $primary.find('.slot-weekday').val(String(weekday));

    const service = catalog?.services?.find(
        x => Number(x.id) === Number($service.val())
    );

    if (service?.duration) {
        $primary.find('.slot-duration').val(service.duration);
    }
}

function reset(){clear();editingSeriesId=null;category=null;$cats.empty();$service.empty().prop('disabled',true);$provider.empty().prop('disabled',true);$employees.empty().append('<span class="text-muted">Zuerst eine Dienstleistung auswählen.</span>');$slots.empty();$preview.empty();$save.text('Serientermin anlegen').prop('disabled',false);$('#series-planner-modal .modal-header h3').text('Serientermin planen');}
function values(){const s=catalog.services.find(x=>Number(x.id)===Number($service.val())),end=$('#series-end-type').val(),v=Number($('#series-end-value').val());return{name:s?.name||'',group_name:null,id_services:Number($service.val()),id_users_provider:Number($provider.val()),employee_ids:$employees.find('.series-employee:checked').map((_,element)=>Number(element.value)).get(),start_date:$('#series-start-date').val(),recurrence_type:'weekly',recurrence_interval:Number($('#series-recurrence-interval').val()),end_type:end,week_count:end==='weeks'?v:null,occurrence_count:end==='count'?v:null,end_date:end==='date'?$('#series-end-date').val():null,capacity:$('#series-capacity-enabled').prop('checked')?Number($('#series-capacity').val()):null,capacity_mode:$('#series-capacity-enabled').prop('checked')?'limited':'unlimited',visibility:$('#series-visibility').val(),course_state:$('#series-course-state').val()||'tentative',location:$('#series-location').val(),color:s?.color||'#7cbae8',slots:$slots.find('.series-slot-row').map((_,r)=>({
    weekday: $(r).find('.slot-weekday').val() === ''
        ? null
        : Number($(r).find('.slot-weekday').val()),
    start_time: normalizeSeriesTime($(r).find('.slot-time').val()),
    duration_minutes: Number($(r).find('.slot-duration').val())
})).get()}}
function validate(x){if(!category||!x.id_services||!x.id_users_provider||!x.start_date)throw Error('Kategorie, Dienstleistung, Initiator und Startdatum ausfüllen.');if(!x.slots.length)throw Error('Mindestens eine Terminzeit eintragen.');
if(x.slots.some(slot => slot.weekday === null))throw Error('Bitte für jede Terminzeit einen Wochentag auswählen.');
if(x.slots.some(slot => !slot.start_time))throw Error('Bitte eine gültige Startzeit eingeben, zum Beispiel 1500 oder 15:00.');
if(x.slots.some(slot => !slot.duration_minutes || slot.duration_minutes < 1))throw Error('Bitte eine gültige Dauer eingeben.');}
function openNew(){reset();loadCatalog().done(()=>{drawCategories();modal.show()}).fail(()=>message('Katalog konnte nicht geladen werden.'))}
function openEdit(id){reset();editingSeriesId=Number(id);$.when(loadCatalog(),$.get(App.Utils.Url.siteUrl('series_planner/load/'+id))).done((catResult,seriesResult)=>{catalog=catResult[0]||catResult;const r=seriesResult[0]||seriesResult,s=r.series;drawCategories();chooseCategory(s.id_service_categories);$service.val(String(s.id_services));chooseService(s.employee_ids||[]);$provider.val(String(s.id_users_provider));renderEmployees(s.employee_ids||[]);$('#series-start-date').val(s.start_date);$('#series-recurrence-interval').val(s.recurrence_interval||1);$('#series-end-type').val(s.end_type||'count').trigger('change');$('#series-end-value').val(s.end_type==='count'?s.occurrence_count:1);$('#series-end-date').val(s.end_date||'');$('#series-capacity-enabled').prop('checked',Number(s.capacity)>0).trigger('change');$('#series-capacity').val(s.capacity||1);$('#series-visibility').val(s.visibility||'public');$('#series-course-state').val(s.course_state||'tentative');$('#series-location').val(s.location||'');$slots.empty();(s.slots||[]).forEach(slotRow);$('#series-planner-modal .modal-header h3').text('Serientermin bearbeiten');$save.text('Serientermin aktualisieren');modal.show()}).fail(x=>message(x.responseJSON?.message||'Serie konnte nicht geladen werden.'))}
$(document).on('click','#new-appointment-series',openNew);$(document).on('scheduler:edit-series',(_,id)=>openEdit(id));$cats.on('click','.series-category-card',e=>chooseCategory($(e.currentTarget).data('id')));$service.on('change',()=>{
    chooseService();
    syncPrimarySlot();
});
$provider.on('change',()=>renderEmployees(
    $employees.find('.series-employee:checked')
        .map((_,element)=>Number(element.value))
        .get()
));
$('#series-start-date').on('change',syncPrimarySlot);$('#series-add-slot').on('click',()=>slotRow({weekday:'',start_time:'',duration_minutes:Number($('.slot-duration:first').val())||60}));$slots.on('click','.slot-remove',e=>$(e.currentTarget).closest('.series-slot-row').remove());$('#series-end-type').on('change',()=>{const t=$('#series-end-type').val();$('#series-end-value-wrap').toggleClass('d-none',t==='date');$('#series-end-date-wrap').toggleClass('d-none',t!=='date');$('#series-end-value-label').text(t==='weeks'?'Wochen':'Termine')});
$('#series-preview').on('click',()=>{clear();try{const x=values();validate(x);$.post(App.Utils.Url.siteUrl('series_planner/preview'),{csrf_token:vars('csrf_token'),series_data:x}).done(r=>{message(r.occurrences.length+' Termine geprüft.','success')}).fail(z=>message(z.responseJSON?.message||'Vorschau fehlgeschlagen.'))}catch(e){message(e.message)}});
$save.on('click',()=>{clear();try{const x=values();validate(x);$save.prop('disabled',true);const endpoint=editingSeriesId?'series_planner/update':'series_planner/create',payload={csrf_token:vars('csrf_token'),series_data:x};if(editingSeriesId)payload.series_id=editingSeriesId;$.post(App.Utils.Url.siteUrl(endpoint),payload).done(r=>{let text=editingSeriesId?'Serientermin aktualisiert.':r.occurrence_count+' Termine angelegt.';if(r.protected_appointment_ids?.length)text+=' '+r.protected_appointment_ids.length+' gebuchte alte Termine wurden geschützt.';App.Layouts.Backend.displayNotification(text);modal.hide();$('#reload-appointments').trigger('click')}).fail(z=>{message(z.responseJSON?.message||'Serie konnte nicht gespeichert werden.');$save.prop('disabled',false)})}catch(e){message(e.message);$save.prop('disabled',false)}});
});
