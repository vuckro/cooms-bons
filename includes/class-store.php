<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;

final class Store {
    public const MANAGE = 'cooms_manage_bons';
    public const REDEEM = 'cooms_redeem_bons';
    public const TITLE = 'Boîte de 4 cookies';
    public const DEFAULT_CENTS = 2000;
    public const DEFAULT_PICKUP = 'Retrait en boutique COOMS';
    public const MAX_VALUE_CENTS = 10000000;
    public const MAX_BATCH = 250;

    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . 'cooms_' . $name;
    }

    public static function install(bool $network_wide = false): void {
        if ($network_wide) { wp_die('Activez COOMS Bons séparément sur chaque site, sans activation réseau.'); }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        $lots = self::table('lots');
        $bons = self::table('bons');
        dbDelta("CREATE TABLE $lots (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(160) NOT NULL,
            title varchar(190) NOT NULL,
            value_cents int unsigned NOT NULL DEFAULT 0,
            pickup_text varchar(160) NOT NULL DEFAULT 'Retrait en boutique COOMS',
            description text DEFAULT NULL,
            quantity int unsigned NOT NULL,
            request_key varchar(64) NOT NULL,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            created_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY request_key (request_key)
        ) ENGINE=InnoDB $collate;");
        dbDelta("CREATE TABLE $bons (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            lot_id bigint(20) unsigned NOT NULL,
            code varchar(32) NOT NULL,
            token varchar(64) NOT NULL,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            redeemed_at datetime DEFAULT NULL,
            redeemed_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            UNIQUE KEY code (code),
            KEY lot_id (lot_id),
            KEY expires_at (expires_at),
            KEY redeemed_at (redeemed_at)
        ) ENGINE=InnoDB $collate;");
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($lots))) !== $lots ||
            $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($bons))) !== $bons) {
            return;
        }
        add_role('cooms_seller', 'Vendeur COOMS', ['read' => true, self::REDEEM => true]);
        $seller = get_role('cooms_seller');
        if ($seller) { $seller->add_cap(self::REDEEM); }
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap(self::MANAGE);
            $admin->add_cap(self::REDEEM);
        }
        foreach (['description', 'value_cents', 'pickup_text'] as $column) {
            if ($wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $lots LIKE %s", $column)) !== $column) { return; }
        }
        if (get_option('cooms_bons_schema') !== '3') {
            self::migrate_legacy_lots();
        }
        update_option('cooms_bons_schema', '3', false);
    }

    /** Split historical "Gift — XX CHF" titles into gift name + centime value without losing printed labels. */
    public static function migrate_legacy_lots(): void {
        global $wpdb;
        $lots = self::table('lots');
        $wpdb->query($wpdb->prepare("UPDATE $lots SET pickup_text = %s WHERE pickup_text = ''", self::DEFAULT_PICKUP));
        $rows = $wpdb->get_results("SELECT id, title, value_cents FROM $lots WHERE value_cents = 0");
        foreach ($rows as $row) {
            [$clean_title, $cents] = self::extract_legacy_gift((string) $row->title);
            if ($cents !== null && $clean_title !== '') {
                $wpdb->update($lots, ['title' => $clean_title, 'value_cents' => $cents], ['id' => (int) $row->id], ['%s', '%d'], ['%d']);
            }
        }
    }

    /** @return array{0: string, 1: ?int} */
    private static function extract_legacy_gift(string $title): array {
        if (preg_match('/^(.+?)\s*[—–-]\s*(\d{1,6}(?:[.,]\d{1,2})?)\s*CHF\s*$/iu', trim($title), $match)) {
            $parsed = self::parse_value($match[2]);
            if (!is_wp_error($parsed)) {
                return [trim($match[1]), $parsed];
            }
        }
        return [$title, null];
    }

    /** @param string|int|float $raw @return int|\WP_Error */
    public static function parse_value($raw) {
        $text = str_replace(["\xC2\xA0", "\xE2\x80\xAF", ' ', "'"], '', trim((string) $raw));
        $text = str_replace(',', '.', $text);
        if ($text === '' || !preg_match('/^\d{1,6}(?:\.(\d{1,2}))?$/D', $text, $match)) {
            return new \WP_Error('invalid_value', 'Indiquez une valeur unitaire en CHF entre 0 et 100 000 (ex. 20 ou 24.50).');
        }
        $parts = explode('.', $text, 2);
        $cents = ((int) $parts[0]) * 100 + (isset($parts[1]) ? (int) str_pad($parts[1], 2, '0') : 0);
        if ($cents < 0 || $cents > self::MAX_VALUE_CENTS) {
            return new \WP_Error('invalid_value', 'Indiquez une valeur unitaire en CHF entre 0 et 100 000 (ex. 20 ou 24.50).');
        }
        return $cents;
    }

    public static function format_chf(int $cents): string {
        $cents = max(0, $cents);
        return ($cents % 100 === 0 ? number_format_i18n(intdiv($cents, 100), 0) : number_format_i18n($cents / 100, 2)) . ' CHF';
    }

    public static function csv_chf(int $cents): string {
        $cents = max(0, $cents);
        return $cents % 100 === 0 ? (string) intdiv($cents, 100) : sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    public static function input_chf(int $cents): string {
        return self::csv_chf($cents);
    }

    public static function gift_label(object $item): string {
        $title = trim((string) ($item->title ?? ''));
        $cents = (int) ($item->value_cents ?? 0);
        if ($cents <= 0 || preg_match('/\bCHF\b/iu', $title)) { return $title; }
        return $title . ' — ' . self::format_chf($cents);
    }

    /** @return string|\WP_Error */
    public static function pickup_text(string $value) {
        $value = sanitize_text_field($value);
        if ($value === '' || mb_strlen($value) > 160) {
            return new \WP_Error('invalid_pickup', 'Indiquez un texte de retrait de 1 à 160 caractères (ex. Retrait en boutique Cornavin ou Terrassière).');
        }
        return $value;
    }

    public static function pickup_line(object $item): string {
        $pickup = trim((string) ($item->pickup_text ?? ''));
        if ($pickup === '') { $pickup = self::DEFAULT_PICKUP; }
        if (preg_match('/^(?:usage unique|un seul retrait)\b/iu', $pickup)) { return $pickup; }
        return 'Usage unique · ' . $pickup;
    }

    public static function update_pickup(int $id, string $value) {
        if (!current_user_can(self::MANAGE)) { return new \WP_Error('forbidden', 'Accès réservé au responsable.'); }
        $value = self::pickup_text($value);
        if (is_wp_error($value)) { return $value; }
        if (!self::lot($id)) { return new \WP_Error('missing', 'Lot introuvable.'); }
        global $wpdb;
        return $wpdb->update(self::table('lots'), ['pickup_text' => $value], ['id' => $id]) === false
            ? new \WP_Error('storage', 'Le texte de retrait n’a pas pu être enregistré. Réessayez.') : true;
    }

    /** Six calendar months, clamped to the last day of the destination month. */
    public static function expiry(\DateTimeImmutable $date): string {
        $local = $date->setTimezone(new \DateTimeZone('Europe/Zurich'));
        $target = $local->modify('first day of this month')->modify('+6 months');
        $day = min((int) $local->format('j'), (int) $target->format('t'));
        // Store the exclusive boundary (midnight after the printed last valid day).
        return $target->setDate((int) $target->format('Y'), (int) $target->format('m'), $day)
            ->setTime(0, 0)->modify('+1 day')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /** End of the selected inclusive Geneva day, stored as exclusive UTC midnight. */
    public static function selected_expiry(string $day) {
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $day)) {
            return new \WP_Error('invalid_expiry', 'Choisissez une date de fin de validité à partir d’aujourd’hui.');
        }
        $zone = new \DateTimeZone('Europe/Zurich');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $day, $zone);
        if (!$date || $date->format('Y-m-d') !== $day || $day > '9999-12-30' || $day < (new \DateTimeImmutable('now', $zone))->format('Y-m-d')) {
            return new \WP_Error('invalid_expiry', 'Choisissez une date de fin de validité à partir d’aujourd’hui.');
        }
        return $date->modify('+1 day')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public static function now(): string { return gmdate('Y-m-d H:i:s'); }

    public static function status(object $bon, ?string $at = null): string {
        if ($bon->redeemed_at) { return 'used'; }
        return $bon->expires_at <= ($at ?? self::now()) ? 'expired' : 'available';
    }

    public static function format_date(string $utc, bool $time = false): string {
        $date = new \DateTimeImmutable($utc, new \DateTimeZone('UTC'));
        return $date->setTimezone(new \DateTimeZone('Europe/Zurich'))->format($time ? 'd/m/Y à H:i:s' : 'd/m/Y');
    }

    public static function expiry_label(string $exclusive): string {
        return self::format_date((new \DateTimeImmutable($exclusive, new \DateTimeZone('UTC')))->modify('-1 second')->format('Y-m-d H:i:s'));
    }

    /** @return int|\WP_Error */
    public static function create_lot(
        string $name,
        int $quantity,
        string $key,
        string $gift = self::TITLE,
        ?string $valid_until = null,
        string $description = '',
        ?int $value_cents = null,
        string $pickup_text = self::DEFAULT_PICKUP
    ) {
        global $wpdb;
        $name = sanitize_text_field($name);
        if ($name === '' || mb_strlen($name) > 160 || $quantity < 1 || $quantity > self::MAX_BATCH || !preg_match('/^[a-f0-9]{64}$/D', $key)) {
            return new \WP_Error('invalid_lot', 'Indiquez un nom (160 caractères maximum) et une quantité de 1 à ' . self::MAX_BATCH . '.');
        }
        $gift = sanitize_text_field($gift);
        if ($value_cents === null) {
            [$clean_gift, $extracted] = self::extract_legacy_gift($gift);
            if ($extracted !== null && $clean_gift !== '') {
                $gift = $clean_gift;
                $value_cents = $extracted;
            } else {
                $value_cents = self::DEFAULT_CENTS;
            }
        }
        if ($gift === '' || mb_strlen($gift) > 190) { return new \WP_Error('invalid_gift', 'Indiquez un nom de cadeau de 1 à 190 caractères.'); }
        if ($value_cents < 0 || $value_cents > self::MAX_VALUE_CENTS) {
            return new \WP_Error('invalid_value', 'Indiquez une valeur unitaire en CHF entre 0 et 100 000.');
        }
        $pickup_text = self::pickup_text($pickup_text);
        if (is_wp_error($pickup_text)) { return $pickup_text; }
        $description = self::description($description);
        if (is_wp_error($description)) { return $description; }
        $expires = $valid_until === null ? self::expiry(new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) : self::selected_expiry($valid_until);
        if (is_wp_error($expires)) { return $expires; }
        $lots = self::table('lots');
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $lots WHERE request_key = %s", $key));
        if ($existing) { return (int) $existing; }
        $now = self::now();
        $wpdb->query('START TRANSACTION');
        try {
            $created = $wpdb->insert($lots, [
                'name' => $name,
                'title' => $gift,
                'value_cents' => $value_cents,
                'pickup_text' => $pickup_text,
                'description' => $description,
                'quantity' => $quantity,
                'request_key' => $key,
                'created_at' => $now,
                'expires_at' => $expires,
                'created_by' => get_current_user_id(),
            ]);
            if (!$created) {
                $wpdb->query('ROLLBACK');
                // A concurrent submission may have committed this same request.
                $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $lots WHERE request_key = %s", $key));
                return $existing ? (int) $existing : new \WP_Error('storage', 'Le lot n’a pas pu être créé. Réessayez avec ce formulaire.');
            }
            $id = (int) $wpdb->insert_id;
            for ($i = 0; $i < $quantity; $i++) {
                $token = bin2hex(random_bytes(32));
                $code = 'COOMS-' . strtoupper(bin2hex(random_bytes(5)));
                if (!$wpdb->insert(self::table('bons'), ['lot_id' => $id, 'code' => $code, 'token' => $token,
                    'created_at' => $now, 'expires_at' => $expires])) {
                    throw new \RuntimeException('insert');
                }
            }
            if ($wpdb->query('COMMIT') === false) { throw new \RuntimeException('commit'); }
            return $id;
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('storage', 'Aucun lot complet n’a pu être enregistré. Rechargez la liste avant de réessayer.');
        }
    }

    public static function find(string $token): ?object {
        global $wpdb;
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) { return null; }
        $bons = self::table('bons'); $lots = self::table('lots');
        return $wpdb->get_row($wpdb->prepare("SELECT b.*, l.name AS lot_name, l.title, l.value_cents, l.pickup_text FROM $bons b INNER JOIN $lots l ON l.id=b.lot_id WHERE b.token=%s", $token));
    }

    public static function find_code(string $code): ?object {
        global $wpdb;
        if (!current_user_can(self::REDEEM) || !preg_match('/^COOMS-[A-F0-9]{10}$/D', $code)) { return null; }
        $token = $wpdb->get_var($wpdb->prepare('SELECT token FROM ' . self::table('bons') . ' WHERE code=%s', $code));
        return $token ? self::find($token) : null;
    }

    public static function lot_summaries(int $limit = 30, int $offset = 0): array {
        global $wpdb;
        $l = self::table('lots'); $b = self::table('bons'); $now = self::now();
        return $wpdb->get_results($wpdb->prepare("SELECT l.*, COALESCE(c.used,0) AS used, COALESCE(c.available,0) AS available, COALESCE(c.expired,0) AS expired FROM $l l LEFT JOIN
            (SELECT lot_id, SUM(redeemed_at IS NOT NULL) AS used, SUM(redeemed_at IS NULL AND expires_at > %s) AS available, SUM(redeemed_at IS NULL AND expires_at <= %s) AS expired FROM $b GROUP BY lot_id) c ON c.lot_id=l.id ORDER BY l.id DESC LIMIT %d OFFSET %d", $now, $now, $limit, $offset));
    }

    /** No check-then-write: eligibility and consumption occur in one database statement. */
    public static function redeem(string $token): array {
        global $wpdb;
        if (!current_user_can(self::REDEEM)) { return ['result' => 'forbidden', 'bon' => null]; }
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) { return ['result' => 'missing', 'bon' => null]; }
        $table = self::table('bons'); $now = self::now();
        $updated = $wpdb->query($wpdb->prepare("UPDATE $table SET redeemed_at=%s, redeemed_by=%d WHERE token=%s AND redeemed_at IS NULL AND expires_at > %s", $now, get_current_user_id(), $token, $now));
        $bon = self::find($token);
        return ['result' => $updated === false ? 'error' : ($updated === 1 ? 'success' : ($bon ? self::status($bon) : 'missing')), 'bon' => $bon];
    }

    public static function lot(int $id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('lots') . ' WHERE id=%d', $id));
    }

    /** Private operational notes; never joined into public voucher data. */
    public static function description(string $value) {
        $value = sanitize_textarea_field($value);
        return mb_strlen($value) > 2000 ? new \WP_Error('invalid_description', 'La description interne est limitée à 2 000 caractères.') : $value;
    }

    public static function update_description(int $id, string $value) {
        if (!current_user_can(self::MANAGE)) { return new \WP_Error('forbidden', 'Accès réservé au responsable.'); }
        $value = self::description($value);
        if (is_wp_error($value)) { return $value; }
        if (!self::lot($id)) { return new \WP_Error('missing', 'Lot introuvable.'); }
        global $wpdb;
        return $wpdb->update(self::table('lots'), ['description' => $value], ['id' => $id]) === false
            ? new \WP_Error('storage', 'La description n’a pas pu être enregistrée. Réessayez.') : true;
    }

    public static function lots(): array {
        global $wpdb;
        return $wpdb->get_results('SELECT * FROM ' . self::table('lots') . ' ORDER BY id DESC');
    }

    private static function where(int $lot, string $status, ?string $at = null): array {
        $sql = '1=1'; $args = [];
        if ($lot) { $sql .= ' AND b.lot_id=%d'; $args[] = $lot; }
        if ($status === 'used') { $sql .= ' AND b.redeemed_at IS NOT NULL'; }
        if ($status === 'available' || $status === 'expired') {
            $sql .= ' AND b.redeemed_at IS NULL AND b.expires_at ' . ($status === 'available' ? '>' : '<=') . ' %s'; $args[] = $at ?? self::now();
        }
        return [$sql, $args];
    }

    public static function bons(int $lot = 0, string $status = '', int $limit = 30, int $offset = 0, ?string $at = null): array {
        global $wpdb;
        [$where, $args] = self::where($lot, $status, $at);
        $b = self::table('bons'); $l = self::table('lots');
        $args[] = max(1, $limit); $args[] = max(0, $offset);
        return $wpdb->get_results($wpdb->prepare("SELECT b.*, l.name AS lot_name, l.title, l.value_cents, l.pickup_text, u.display_name AS redeemer_name FROM $b b INNER JOIN $l l ON b.lot_id=l.id LEFT JOIN {$wpdb->users} u ON u.ID=b.redeemed_by WHERE $where ORDER BY b.id DESC LIMIT %d OFFSET %d", $args));
    }

    /** Attribution is private to management; deleted accounts retain their audit ID. */
    public static function redeemer_label(object $bon): string {
        if (!$bon->redeemed_at) { return ''; }
        if (!empty($bon->redeemer_name)) { return $bon->redeemer_name; }
        return $bon->redeemed_by ? 'Compte supprimé (#' . (int) $bon->redeemed_by . ')' : 'Non renseigné';
    }

    public static function counts(int $lot = 0): array {
        global $wpdb;
        [$where, $args] = self::where($lot, '');
        $now = self::now();
        $sql = 'SELECT COUNT(*) AS total, COALESCE(SUM(redeemed_at IS NOT NULL),0) AS used, COALESCE(SUM(redeemed_at IS NULL AND expires_at > %s),0) AS available, COALESCE(SUM(redeemed_at IS NULL AND expires_at <= %s),0) AS expired FROM ' . self::table('bons') . " b WHERE $where";
        return array_map('intval', (array) $wpdb->get_row($wpdb->prepare($sql, array_merge([$now, $now], $args)), ARRAY_A));
    }

    /** Exact centime totals across one lot or all lots, even when unit values differ. */
    public static function amounts(int $lot = 0): array {
        global $wpdb;
        [$where, $args] = self::where($lot, '');
        $now = self::now();
        $b = self::table('bons'); $l = self::table('lots');
        $sql = "SELECT COALESCE(SUM(l.value_cents),0) AS total_cents,
            COALESCE(SUM(CASE WHEN b.redeemed_at IS NOT NULL THEN l.value_cents ELSE 0 END),0) AS used_cents,
            COALESCE(SUM(CASE WHEN b.redeemed_at IS NULL AND b.expires_at > %s THEN l.value_cents ELSE 0 END),0) AS available_cents,
            COALESCE(SUM(CASE WHEN b.redeemed_at IS NULL AND b.expires_at <= %s THEN l.value_cents ELSE 0 END),0) AS expired_cents
            FROM $b b INNER JOIN $l l ON l.id=b.lot_id WHERE $where";
        return array_map('intval', (array) $wpdb->get_row($wpdb->prepare($sql, array_merge([$now, $now], $args)), ARRAY_A));
    }

    public static function url(object $bon): string {
        return add_query_arg('cooms_bon', $bon->token, home_url('/'));
    }
}
