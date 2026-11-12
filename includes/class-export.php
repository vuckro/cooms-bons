<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;

final class Export {
    public static function ready(): bool {
        return extension_loaded('gd') && (gd_info()['FreeType Support'] ?? false) && function_exists('imagettftext') && class_exists('ZipArchive') && is_readable(dirname(COOMS_BONS_FILE) . '/vendor/autoload.php');
    }

    /** No public archives; temporary files live only for the duration of the request. */
    public static function zip(int $lot_id): void {
        $lot = Store::lot($lot_id);
        if (!$lot) { wp_die('Lot introuvable.', '', ['response' => 404]); }
        if (!self::ready()) { wp_die('Export indisponible : vérifiez GD, FreeType, ZIP et les dépendances du plugin.', '', ['response' => 503]); }
        require_once dirname(COOMS_BONS_FILE) . '/vendor/autoload.php';
        $path = tempnam(sys_get_temp_dir(), 'cooms-');
        if (!$path) { wp_die('Impossible de préparer le téléchargement.', '', ['response' => 503]); }
        $zip = new \ZipArchive();
        try {
            if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) { throw new \RuntimeException('zip'); }
            $bons = Store::bons($lot_id, '', Store::MAX_BATCH);
            if (count($bons) !== (int) $lot->quantity) { throw new \RuntimeException('incomplete'); }
            foreach ($bons as $bon) {
                if (!$zip->addFromString($bon->code . '.png', self::png($bon))) { throw new \RuntimeException('image'); }
            }
            if (!$zip->close()) { throw new \RuntimeException('close'); }
            nocache_headers();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="cooms-lot-' . $lot_id . '.zip"');
            header('Content-Length: ' . filesize($path));
            header('X-Content-Type-Options: nosniff');
            readfile($path);
        } catch (\Throwable $e) {
            try { $zip->close(); } catch (\Throwable $ignored) { /* Archive may already be closed. */ }
            if (is_file($path)) { unlink($path); }
            wp_die('Le ZIP n’a pas pu être préparé. Les bons sont conservés : relancez le téléchargement depuis le lot.', '', ['response' => 503]);
        } finally {
            if (is_file($path)) { unlink($path); }
        }
        exit;
    }

    /** Pixel-based wrapping, including long unbroken names. */
    public static function gift_lines(string $value, string $font, int $size): array {
        $lines = []; $line = '';
        $fits = static function (string $text) use ($size, $font): bool {
            $box = imagettfbbox($size, 0, $font, $text);
            return $box[2] - $box[0] <= 860;
        };
        foreach (preg_split('/\s+/u', trim($value)) as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($fits($candidate)) { $line = $candidate; continue; }
            if ($line !== '') { $lines[] = $line; $line = ''; }
            foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $character) {
                if (!$fits($line . $character) && $line !== '') { $lines[] = $line; $line = ''; }
                $line .= $character;
            }
        }
        if ($line !== '') { $lines[] = $line; }
        return $lines;
    }

    public static function png(object $bon): string {
        $qr_code = \Endroid\QrCode\QrCode::create(Store::url($bon))->setSize(540)->setMargin(32)
            ->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::Medium);
        $result = (new \Endroid\QrCode\Writer\PngWriter())->write($qr_code);
        $qr = imagecreatefromstring($result->getString());
        if (!$qr) { throw new \RuntimeException('qr'); }
        $image = imagecreatetruecolor(1000, 1320);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 39, 38, 38);
        $muted = imagecolorallocate($image, 106, 81, 70);
        $cream = imagecolorallocate($image, 245, 222, 203);
        $red = imagecolorallocate($image, 197, 44, 29);
        imagefill($image, 0, 0, $white);
        imagefilledrectangle($image, 0, 0, 999, 240, $cream);
        imagefilledrectangle($image, 0, 1150, 999, 1319, $cream);
        $logo = imagecreatefrompng(dirname(COOMS_BONS_FILE) . '/assets/brand/cooms-logo.png');
        if (!$logo) { throw new \RuntimeException('brand-logo'); }
        imagecopyresampled($image, $logo, 310, 30, 0, 0, 380, 144, imagesx($logo), imagesy($logo));
        imagedestroy($logo);
        $font = dirname(COOMS_BONS_FILE) . '/vendor/endroid/qr-code/assets/open_sans.ttf';
        $text = static function (string $value, int $size, int $y, int $color) use ($image, $font): void {
            $box = imagettfbbox($size, 0, $font, $value);
            imagettftext($image, $size, 0, (int) ((1000 - ($box[2] - $box[0])) / 2), $y, $color, $font, $value);
        };
        $text('Bon cadeau', 24, 218, $red);
        $gift_label = Store::gift_label($bon);
        $size = 28;
        do {
            $lines = self::gift_lines($gift_label, $font, $size);
            if (count($lines) * ($size + 10) <= 116) { break; }
            $size--;
        } while ($size >= 12);
        $baseline = 250 + (116 - count($lines) * ($size + 10)) / 2;
        foreach ($lines as $line) { $text($line, $size, (int) $baseline, $ink); $baseline += $size + 10; }
        imagecopy($image, $qr, (int) ((1000 - imagesx($qr)) / 2), 370, 0, 0, imagesx($qr), imagesy($qr));
        $text($bon->code, 24, 1010, $ink);
        $text('Valable jusqu’au ' . Store::expiry_label($bon->expires_at) . ' inclus', 21, 1080, $ink);
        $pickup_line = Store::pickup_line($bon);
        $p_size = 19;
        do {
            $p_lines = self::gift_lines($pickup_line, $font, $p_size);
            if (count($p_lines) <= 2) { break; }
            $p_size--;
        } while ($p_size >= 10);
        $p_lines = array_slice($p_lines, 0, 2);
        $p_y = count($p_lines) === 1 ? 1185 : 1180;
        foreach ($p_lines as $p_line) {
            $text($p_line, $p_size, $p_y, $muted);
            $p_y += $p_size + 10;
        }
        $text('Présentez ce bon au vendeur avant la remise.', 18, count($p_lines) === 1 ? 1230 : 1258, $muted);
        ob_start(); imagepng($image); $bytes = ob_get_clean();
        imagedestroy($qr); imagedestroy($image);
        return $bytes;
    }

    /** Spreadsheet formula injection must not turn lot names into executable cells. */
    public static function cell(string $value): string {
        return preg_match('/^[\s]*[=+@\-\t\r\n]/u', $value) ? "'" . $value : $value;
    }

    public static function csv(int $lot, string $status): void {
        global $wpdb;
        // A consistent snapshot prevents concurrent generations/redemptions from
        // shifting pages or mixing financial states within the same export.
        if ($wpdb->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ') === false ||
            $wpdb->query('START TRANSACTION WITH CONSISTENT SNAPSHOT') === false) {
            wp_die('Impossible de préparer un export cohérent. Réessayez.', '', ['response' => 503]);
        }
        $as_of = Store::now();
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cooms-retraits-' . gmdate('Y-m-d') . '.csv"');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Code', 'Lot', 'Création (Genève)', 'Valable jusqu’au', 'Statut', 'Retrait (Genève)', 'Cadeau', 'Validé par', 'Valeur unitaire (CHF)', 'Montant retiré (CHF)', 'Texte de retrait'], ';', '"', '');
        $labels = ['used' => 'Utilisé', 'available' => 'Disponible', 'expired' => 'Expiré'];
        $offset = 0;
        do {
            $rows = Store::bons($lot, $status, 500, $offset, $as_of);
            foreach ($rows as $bon) {
                $state = Store::status($bon, $as_of);
                $cents = (int) ($bon->value_cents ?? 0);
                $pickup = !empty($bon->pickup_text) ? $bon->pickup_text : Store::DEFAULT_PICKUP;
                $line = [
                    $bon->code,
                    $bon->lot_name,
                    Store::format_date($bon->created_at, true),
                    Store::expiry_label($bon->expires_at),
                    $labels[$state],
                    $bon->redeemed_at ? Store::format_date($bon->redeemed_at, true) : '',
                    $bon->title,
                    Store::redeemer_label($bon),
                    Store::csv_chf($cents),
                    Store::csv_chf($state === 'used' ? $cents : 0),
                    $pickup,
                ];
                fputcsv($out, array_map([self::class, 'cell'], $line), ';', '"', '');
            }
            $offset += count($rows);
        } while (count($rows) === 500);
        $wpdb->query('COMMIT');
        fclose($out);
        exit;
    }
}
