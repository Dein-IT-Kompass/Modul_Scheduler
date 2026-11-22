#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="/tmp/modul-products-$STAMP"
mkdir -p "$BACKUP_DIR"

FILES=(
  application/config/autoload.php
  application/controllers/Installation.php
  application/controllers/General_settings.php
  application/controllers/Planning_projects.php
  application/controllers/Planning_resources.php
  application/controllers/Planning_resource_categories.php
  application/views/components/backend_header.php
  application/views/pages/installation.php
  application/views/pages/general_settings.php
  assets/js/pages/installation.js
  assets/js/pages/general_settings.js
)

for f in "${FILES[@]}"; do
  [[ -f "$f" ]] || { echo "FEHLER: Datei fehlt: $f" >&2; exit 1; }
  mkdir -p "$BACKUP_DIR/$(dirname "$f")"
  cp "$f" "$BACKUP_DIR/$f"
done

echo "Backup: $BACKUP_DIR"
mkdir -p application/config application/helpers

cat > application/config/modules.php <<'PHP'
<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['modules'] = [
    'scheduler' => [
        'name' => 'MODUL Scheduler',
        'required' => true,
        'description' => 'Termin-, Kunden-, Personal- und Kalenderverwaltung.',
    ],
    'erp' => [
        'name' => 'MODUL ERP',
        'required' => false,
        'requires' => ['scheduler'],
        'description' => 'Projekt-, Einsatz- und Ressourcenplanung.',
    ],
];
PHP

cat > application/helpers/module_helper.php <<'PHP'
<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('enabled_modules')) {
    function enabled_modules(): array
    {
        $raw = setting('enabled_modules');
        $modules = json_decode((string) $raw, true);

        if (!is_array($modules)) {
            $modules = ['scheduler'];
        }

        $modules = array_values(array_unique(array_map('strval', $modules)));

        if (!in_array('scheduler', $modules, true)) {
            array_unshift($modules, 'scheduler');
        }

        return $modules;
    }
}

if (!function_exists('module_enabled')) {
    function module_enabled(string $module): bool
    {
        return in_array($module, enabled_modules(), true);
    }
}

if (!function_exists('require_module')) {
    function require_module(string $module): void
    {
        if (!module_enabled($module)) {
            abort(404, 'Not Found');
        }
    }
}
PHP

python3 <<'PY'
from pathlib import Path
import re


def load(path):
    return Path(path).read_text(encoding='utf-8')


def save(path, text):
    Path(path).write_text(text, encoding='utf-8', newline='\n')

# 1. Autoload helper.
p = 'application/config/autoload.php'
t = load(p)
if "'module'" not in t and '"module"' not in t:
    m = re.search(r"\$autoload\['helper'\]\s*=\s*\[([^\]]*)\];", t, re.S)
    if not m:
        raise SystemExit('FEHLER: $autoload[helper] nicht gefunden.')
    body = m.group(1).rstrip()
    if body and not body.endswith(','):
        body += ','
    body += "\n    'module',\n"
    t = t[:m.start(1)] + body + t[m.end(1):]
    save(p, t)

# 2. ERP controller guards.
for p in [
    'application/controllers/Planning_projects.php',
    'application/controllers/Planning_resources.php',
    'application/controllers/Planning_resource_categories.php',
]:
    t = load(p)
    if "require_module('erp');" not in t:
        marker = '        parent::__construct();\n'
        if marker not in t:
            raise SystemExit(f'FEHLER: Konstruktor-Marker fehlt: {p}')
        t = t.replace(marker, marker + "\n        require_module('erp');\n", 1)
        save(p, t)

# 3. Wrap the complete Planning navigation list item.
p = 'application/views/components/backend_header.php'
t = load(p)
if "module_enabled('erp')" not in t:
    hit = min([x for x in [t.find('planning_projects'), t.find('planning_resources')] if x >= 0], default=-1)
    if hit < 0:
        raise SystemExit('FEHLER: Planungsnavigation nicht gefunden.')
    starts = [m.start() for m in re.finditer(r'<li\b', t[:hit])]
    if not starts:
        raise SystemExit('FEHLER: Start des Planungsmenüs nicht gefunden.')
    start = starts[-1]
    token_re = re.compile(r'<li\b|</li\s*>', re.I)
    depth = 0
    end = None
    for m in token_re.finditer(t, start):
        if m.group(0).lower().startswith('<li'):
            depth += 1
        else:
            depth -= 1
            if depth == 0:
                end = m.end()
                break
    if end is None:
        raise SystemExit('FEHLER: Ende des Planungsmenüs nicht gefunden.')
    block = t[start:end]
    wrapped = "<?php if (module_enabled('erp')): ?>\n" + block + "\n<?php endif; ?>"
    t = t[:start] + wrapped + t[end:]
    save(p, t)

# 4. Installer controller.
p = 'application/controllers/Installation.php'
t = load(p)
if "check('enabled_modules'" not in t:
    marker = "            check('company', 'array');\n"
    if marker not in t:
        raise SystemExit('FEHLER: Installer company check fehlt.')
    t = t.replace(marker, marker + "            check('enabled_modules', 'array|null');\n", 1)
if '$allowed_modules' not in t:
    marker = "            $company = request('company');\n"
    if marker not in t:
        raise SystemExit('FEHLER: Installer company request fehlt.')
    addition = '''

            $allowed_modules = ['scheduler', 'erp'];
            $enabled_modules = array_values(array_unique(array_intersect(
                array_map('strval', request('enabled_modules', ['scheduler'])),
                $allowed_modules
            )));

            if (!in_array('scheduler', $enabled_modules, true)) {
                array_unshift($enabled_modules, 'scheduler');
            }
'''
    t = t.replace(marker, marker + addition, 1)
if "'enabled_modules' => json_encode($enabled_modules" not in t:
    marker = "                'company_link' => $company['company_link'],\n"
    if marker not in t:
        raise SystemExit('FEHLER: Installer settings block fehlt.')
    addition = "                'enabled_modules' => json_encode($enabled_modules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),\n"
    t = t.replace(marker, marker + addition, 1)
save(p, t)

# 5. Installer view.
p = 'application/views/pages/installation.php'
t = load(p)
if 'id="product-scheduler"' not in t:
    candidates = ['            <p class="mb-5">\n', '            <div class="mb-3">\n']
    marker = next((x for x in candidates if x in t), None)
    if not marker:
        raise SystemExit('FEHLER: Installer UI marker fehlt.')
    block = '''            <div class="card mb-5" id="module-product-selection">
                <div class="card-header"><strong>MODUL-Produkte</strong></div>
                <div class="card-body">
                    <div class="form-check mb-4">
                        <input class="form-check-input product-selection" type="radio"
                               name="product-selection" id="product-scheduler"
                               value="scheduler" checked>
                        <label class="form-check-label" for="product-scheduler">
                            <strong>MODUL Scheduler</strong><br>
                            <span class="text-muted">Termine, Kunden, Mitarbeiter, Teams, Leistungen und Kalender.</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input product-selection" type="radio"
                               name="product-selection" id="product-erp" value="erp">
                        <label class="form-check-label" for="product-erp">
                            <strong>MODUL Scheduler + ERP</strong><br>
                            <span class="text-muted">Zusätzlich Projekte, Projektzeitplan, Einsätze, Ressourcenverwaltung und Disposition.</span>
                        </label>
                    </div>
                </div>
            </div>

'''
    t = t.replace(marker, block + marker, 1)
    save(p, t)

# 6. Installer JS.
p = 'assets/js/pages/installation.js'
t = load(p)
if 'enabled_modules: getEnabledModules()' not in t:
    marker = '            company: getCompanyData(),\n'
    if marker not in t:
        raise SystemExit('FEHLER: Installer JS payload fehlt.')
    t = t.replace(marker, marker + '            enabled_modules: getEnabledModules(),\n', 1)
if 'function getEnabledModules()' not in t:
    marker = '    /**\n     * Get the company data as an object.'
    if marker not in t:
        raise SystemExit('FEHLER: Installer JS helper marker fehlt.')
    helper = '''    function getEnabledModules() {
        return $('.product-selection:checked').val() === 'erp'
            ? ['scheduler', 'erp']
            : ['scheduler'];
    }

'''
    t = t.replace(marker, helper + marker, 1)
save(p, t)

# 7. General settings whitelist.
p = 'application/controllers/General_settings.php'
t = load(p)
if "'enabled_modules'," not in t:
    marker = "        'resource_icon_packs',\n" if "        'resource_icon_packs',\n" in t else "        'theme',\n"
    if marker not in t:
        raise SystemExit('FEHLER: Settings whitelist marker fehlt.')
    t = t.replace(marker, marker + "        'enabled_modules',\n", 1)
save(p, t)

# 8. General settings view.
p = 'application/views/pages/general_settings.php'
t = load(p)
if 'id="enabled-modules"' not in t:
    marker = "                        <div class=\"row mb-5\">\n                            <div class=\"col-12\">\n                                <h5 class=\"mb-3 fw-light\"><?= lang('localization') ?></h5>"
    if marker not in t:
        raise SystemExit('FEHLER: Localization marker fehlt.')
    block = '''                        <div class="row mb-5">
                            <div class="col-12">
                                <h5 class="mb-3 fw-light">Installierte Module</h5>
                                <input type="hidden" id="enabled-modules" data-field="enabled_modules">
                                <div class="card mb-3">
                                    <div class="card-body d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <strong>MODUL Scheduler</strong>
                                            <div class="text-muted small">Termin-, Kunden-, Personal- und Kalenderverwaltung.</div>
                                        </div>
                                        <span class="badge bg-success">Aktiv · Basismodul</span>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-body d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <strong>MODUL ERP</strong>
                                            <div class="text-muted small">Projekte, Einsätze, Ressourcen und Disposition. Beim Deaktivieren bleiben vorhandene Daten erhalten.</div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input module-setting"
                                                   id="module-erp" value="erp">
                                            <label class="form-check-label" for="module-erp">Aktiv</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

'''
    t = t.replace(marker, block + marker, 1)
    save(p, t)

# 9. General settings JS.
p = 'assets/js/pages/general_settings.js'
t = load(p)
if "generalSetting.name === 'enabled_modules'" not in t:
    marker = "            if (generalSetting.name === 'company_logo' && generalSetting.value) {"
    if marker not in t:
        raise SystemExit('FEHLER: Settings deserialize marker fehlt.')
    block = '''            if (generalSetting.name === 'enabled_modules') {
                let modules = ['scheduler'];
                try {
                    const parsed = JSON.parse(generalSetting.value || '[]');
                    if (Array.isArray(parsed)) modules = ['scheduler', ...parsed];
                } catch (error) {
                    modules = ['scheduler'];
                }
                modules = [...new Set(modules)];
                $('#module-erp').prop('checked', modules.includes('erp'));
                updateEnabledModulesField();
                return;
            }

'''
    t = t.replace(marker, block + marker, 1)
if 'function updateEnabledModulesField()' not in t:
    marker = '    function serialize() {'
    helper = '''    function updateEnabledModulesField() {
        const modules = ['scheduler'];
        if ($('#module-erp').prop('checked')) modules.push('erp');
        $('#enabled-modules').val(JSON.stringify(modules));
    }

'''
    if marker not in t:
        raise SystemExit('FEHLER: Settings serialize marker fehlt.')
    t = t.replace(marker, helper + marker, 1)
if "$('.module-setting').on('change'" not in t:
    marker = "        $saveSettings.on('click', onSaveSettingsClick);"
    if marker not in t:
        raise SystemExit('FEHLER: Settings initialize marker fehlt.')
    t = t.replace(marker, marker + "\n\n        $('.module-setting').on('change', updateEnabledModulesField);\n        updateEnabledModulesField();", 1)
save(p, t)

print('OK: Modulaktivierung gepatcht.')
PY

# Development installation: keep ERP enabled.
if docker compose ps --services --filter status=running 2>/dev/null | grep -qx mysql; then
  docker compose exec -T mysql mysql --default-character-set=utf8mb4 \
    -uuser -ppassword easyappointments <<'SQL'
INSERT INTO modtest_settings (name, value, create_datetime, update_datetime)
SELECT 'enabled_modules', '["scheduler","erp"]', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM modtest_settings WHERE name = 'enabled_modules'
);
UPDATE modtest_settings
SET value='["scheduler","erp"]', update_datetime=NOW()
WHERE name='enabled_modules';
SQL
fi

PHP_FILES=(
  application/helpers/module_helper.php
  application/controllers/Installation.php
  application/controllers/General_settings.php
  application/controllers/Planning_projects.php
  application/controllers/Planning_resources.php
  application/controllers/Planning_resource_categories.php
  application/views/components/backend_header.php
  application/views/pages/installation.php
  application/views/pages/general_settings.php
)

if docker compose ps --services --filter status=running 2>/dev/null | grep -qx php-fpm; then
  for f in "${PHP_FILES[@]}"; do docker compose exec -T php-fpm php -l "$f"; done
else
  for f in "${PHP_FILES[@]}"; do php -l "$f"; done
fi

if docker compose ps --services --filter status=running 2>/dev/null | grep -qx node; then
  docker compose exec -T node npx gulp scripts
else
  echo "HINWEIS: Node-Container läuft nicht. Später ausführen: docker compose exec node npx gulp scripts"
fi

echo
echo "FERTIG"
echo "Backup: $BACKUP_DIR"
echo "Lokaler Test: Allgemeine Einstellungen -> Installierte Module"
echo "Fresh-Install-Test: neue/leere Datenbank oder neuen Tabellenpräfix verwenden."
