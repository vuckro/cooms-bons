"""Build an installable archive, with dependencies and without test data or Git."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
root = Path(__file__).resolve().parents[1]
assert (root/'vendor/autoload.php').is_file(), 'Run composer install --no-dev first'
output = root/'dist/cooms-bons-1.4.1.zip'
output.parent.mkdir(exist_ok=True)
files = [root/name for name in ['cooms-bons.php', 'README.md', 'readme.txt', 'LICENSE', 'composer.json', 'composer.lock']]
for folder in ['includes', 'templates', 'assets', 'vendor', 'docs']:
    files.extend(p for p in (root/folder).rglob('*') if p.is_file() and p.relative_to(root).as_posix() != 'vendor/endroid/qr-code/assets/noto_sans.otf')
with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(files):
        archive.write(path, 'cooms-bons/'+str(path.relative_to(root)))
print(output)
