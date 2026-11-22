<div id="series-planner-modal"
     class="modal fade"
     tabindex="-1">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">


            <!-- =====================================================
                 HEADER
                 ===================================================== -->

            <div class="modal-header">
                <h3>Serientermin anlegen</h3>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                </button>
            </div>


            <!-- =====================================================
                 BODY
                 ===================================================== -->

            <div class="modal-body">

                <!-- Meldungen / Fehler -->
                <div id="series-planner-message"
                     class="alert d-none">
                </div>


                <!-- =================================================
                     SCHRITT 1
                     Bereich / Kategorie auswählen
                     ================================================= -->

                <section class="series-step">
<div class="series-step-content">

                        <h4>Bereich / Kategorie auswählen</h4>

                        <p class="text-muted">
                            Die Bereiche werden aus den
                            Dienstleistungskategorien geladen.
                        </p>

                        <!-- Wird per JavaScript gefüllt -->
                        <div id="series-category-cards"
                             class="series-category-grid">
                        </div>

                    </div>

                </section>


                <!-- =================================================
                     SCHRITT 2
                     Dienstleistung / Beteiligte / Optionen
                     ================================================= -->

                <section class="series-step">
<div class="series-step-content">

                        <h4>Dienstleistung / Service auswählen</h4>

                        <div class="row g-3">


                            <!-- Dienstleistung -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Dienstleistung
                                </label>

                                <select id="series-service"
                                        class="form-select"
                                        disabled>
                                </select>

                            </div>


                            

                            <!-- Initiator -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Initiator
                                </label>

                                <select id="series-provider"
                                        class="form-select"
                                        disabled>
                                </select>

                            </div>


                            <!-- Weitere Mitarbeiter -->

                            <div class="col-12">
                                <label class="form-label">
                                    Weitere Mitarbeiter
                                </label>

                                <div class="small text-muted mb-2">
                                    Die Verfügbarkeit des Initiators und aller
                                    ausgewählten Mitarbeiter wird geprüft.
                                </div>

                                <div id="series-employees"
                                     class="border rounded p-3">
                                    <span class="text-muted">
                                        Zuerst eine Dienstleistung auswählen.
                                    </span>
                                </div>
                            </div>

                            <!-- Kapazität -->

                            <div class="col-md-3">

                                <label class="form-label">
                                    Kapazität
                                </label>

                                <input id="series-capacity"
                                       type="number"
                                       min="1"
                                       class="form-control"
                                       value="10">

                            </div>


                            <!-- Sichtbarkeit -->

                            <div class="col-md-3">

                                <label class="form-label">
                                    Sichtbarkeit
                                </label>

                                <select id="series-visibility"
                                        class="form-select">

                                    <option value="public">
                                        Öffentlich
                                    </option>

                                    <option value="registered">
                                        Nur registriert
                                    </option>

                                    <option value="internal">
                                        Intern
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     SCHRITT 3
                     Wochenplan / Wiederholung
                     ================================================= -->

                <section class="series-step">
<div class="series-step-content">

                        <h4>Wochenplan festlegen</h4>


                        <!-- -----------------------------------------
                             Grunddaten der Serie
                             ----------------------------------------- -->

                        <div class="row g-3 mb-3">


                            <!-- Startdatum -->

                            <div class="col-md-4">

                                <label class="form-label">
                                    Start der Serie
                                </label>

                                <input id="series-start-date"
                                       type="date"
                                       class="form-control">

                            </div>


                            <!-- Art des Serienendes -->

                            <div class="col-md-4">

                                <label class="form-label">
                                    Serienende
                                </label>

                                <select id="series-end-type"
                                        class="form-select">

                                    <option value="weeks">
                                        Nach Wochen
                                    </option>

                                    <option value="count">
                                        Nach Terminen
                                    </option>

                                    <option value="date">
                                        Am Datum
                                    </option>

                                </select>

                            </div>


                            <!-- Anzahl Wochen / Termine -->

                            <div class="col-md-4"
                                 id="series-end-value-wrap">

                                <label id="series-end-value-label"
                                       class="form-label">
                                    Wochen
                                </label>

                                <input id="series-end-value"
                                       type="number"
                                       min="1"
                                       class="form-control"
                                       value="10">

                            </div>


                            <!-- Konkretes Enddatum -->

                            <div class="col-md-4 d-none"
                                 id="series-end-date-wrap">

                                <label class="form-label">
                                    Enddatum
                                </label>

                                <input id="series-end-date"
                                       type="date"
                                       class="form-control">

                            </div>


                            <!-- Wiederholungsintervall -->

                            <div class="col-md-4">

                                <label class="form-label">
                                    Intervall in Wochen
                                </label>

                                <input id="series-recurrence-interval"
                                       type="number"
                                       min="1"
                                       class="form-control"
                                       value="1">

                            </div>


                            <!-- Veranstaltungsort -->

                            <div class="col-md-8">

                                <label class="form-label">
                                    Ort
                                </label>

                                <input id="series-location"
                                       class="form-control">

                            </div>

                        </div>


                        <!-- -----------------------------------------
                             Wöchentliche Termine
                             ----------------------------------------- -->

                        <div class="d-flex
                                    justify-content-between
                                    align-items-center
                                    mb-2">

                            <div>

                                <strong>
                                    Terminzeiten der Serie
                                </strong>

                                <div class="small text-muted">
                                    Jeder Wochentag kann eine eigene
                                    Startzeit und Dauer haben.
                                </div>

                            </div>


                            <button id="series-add-slot"
                                    type="button"
                                    class="btn btn-outline-secondary">

                                <i class="fas fa-plus me-2"></i>

                                Terminzeit hinzufügen

                            </button>

                        </div>


                        <!-- Wird per JavaScript erzeugt -->
                        <div id="series-slots"
                             class="series-slots">
                        </div>


                        <!-- Status -->

                        <div class="mt-3">

                            <label class="form-label">
                                Status
                            </label>

                            <input class="form-control"
                                   value="Unter Vorbehalt"
                                   disabled>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     SCHRITT 4
                     Vorschau / Konfliktprüfung
                     ================================================= -->

                <section class="series-step">
<div class="series-step-content">

                        <div class="d-flex justify-content-between">

                            <div>

                                <h4>
                                    Vorschau und Verfügbarkeit
                                </h4>

                                <p class="text-muted">
                                    Arbeitsplan, Ausnahmen,
                                    Nichtverfügbarkeiten und Termine
                                    werden geprüft.
                                </p>

                            </div>


                            <button id="series-preview"
                                    type="button"
                                    class="btn btn-outline-secondary">

                                Vorschau berechnen

                            </button>

                        </div>


                        <!-- Vorschauergebnis -->

                        <div id="series-preview-list"
                             class="series-preview-list">

                            <div class="text-muted p-3">
                                Noch keine Vorschau berechnet.
                            </div>

                        </div>

                    </div>

                </section>

            </div>


            <!-- =====================================================
                 FOOTER / AKTIONEN
                 ===================================================== -->

            <div class="modal-footer">

                <button class="btn btn-light"
                        data-bs-dismiss="modal">

                    Abbrechen

                </button>


                <button id="series-create"
                        class="btn btn-primary"
                        disabled>

                    Serie anlegen

                </button>

            </div>


        </div>
    </div>
</div>