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

    public static function png(object $bon): string {
        $qr_code = \Endroid\QrCode\QrCode::create(Store::url($bon))->setSize(540)->setMargin(32)
            ->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::Medium);
        $result = (new \Endroid\QrCode\Writer\PngWriter())->write($qr_code);
        $qr = imagecreatefromstring($result->getString());
        if (!$qr) { throw new \RuntimeException('qr'); }
        $image = imagecreatetruecolor(1000, 1320);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 38, 30, 25);
        $muted = imagecolorallocate($image, 92, 79, 70);
        imagefill($image, 0, 0, $white);
        $font = dirname(COOMS_BONS_FILE) . '/vendor/endroid/qr-code/assets/open_sans.ttf';
        $text = static function (string $value, int $size, int $y, int $color) use ($image, $font): void {
            $box = imagettfbbox($size, 0, $font, $value);
            imagettftext($image, $size, 0, (int) ((1000 - ($box[2] - $box[0])) / 2), $y, $color, $font, $value);
        };
        $text('COOMS', 48, 110, $ink);
        $text('Une boîte de 4 cookies', 30, 205, $ink);
        $text('Bon cadeau · Valeur 20 CHF', 20, 258, $muted);
        imagecopy($image, $qr, (int) ((1000 - imagesx($qr)) / 2), 325, 0, 0, imagesx($qr), imagesy($qr));
        $text($bon->code, 24, 1010, $ink);
        $text('Valable jusqu’au ' . Store::expiry_label($bon->expires_at) . ' inclus', 21, 1080, $ink);
        $text('Usage unique · Retrait en boutique COOMS', 19, 1180, $muted);
        $text('Présentez ce bon au vendeur avant la remise.', 18, 1225, $muted);
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
        fputcsv($out, ['Code', 'Lot', 'Création (Genève)', 'Valable jusqu’au', 'Statut', 'Retrait (Genève)'], ';', '"', '');
        $labels = ['used' => 'Utilisé', 'available' => 'Disponible', 'expired' => 'Expiré'];
        $offset = 0;
        do {
            $rows = Store::bons($lot, $status, 500, $offset, $as_of);
            foreach ($rows as $bon) {
                $line = [$bon->code, $bon->lot_name, Store::format_date($bon->created_at, true), Store::expiry_label($bon->expires_at), $labels[Store::status($bon, $as_of)], $bon->redeemed_at ? Store::format_date($bon->redeemed_at, true) : ''];
                fputcsv($out, array_map([self::class, 'cell'], $line), ';', '"', '');
            }
            $offset += count($rows);
        } while (count($rows) === 500);
        $wpdb->query('COMMIT');
        fclose($out);
        exit;
    }
}
