<?php
/**
 * Pass / line pricing.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Pricing {

    /**
     * @param object[] $items Basket rows with object_id + line_meta
     * @param int      $spots Shared attendee headcount
     * @return array{
     *   spots:int,
     *   workshop_count:int,
     *   pass_applied:bool,
     *   pass_min:int,
     *   list_total:float,
     *   pass_total:float,
     *   discount:float,
     *   total:float,
     *   lines:array
     * }
     */
    public static function calculate_pass(array $items, $spots) {
        $spots           = max(1, (int) $spots);
        $workshop_count  = count($items);
        $pass_min        = DLab_Settings::pass_min_workshops();
        $pass_applied    = $workshop_count >= $pass_min;
        $lines           = array();
        $list_total      = 0.0;
        $pass_total      = 0.0;

        foreach ($items as $item) {
            $post_id = is_object($item) ? (int) $item->object_id : (int) $item;
            $meta    = (is_object($item) && isset($item->line_meta) && is_array($item->line_meta))
                ? $item->line_meta
                : array();

            $list_unit = (float) (DLab_Workshop::get_price_per_person($post_id) ?? 0);
            $pass_unit = DLab_Workshop::get_pass_price($post_id);
            if ($pass_unit === null) {
                $pass_unit = $list_unit;
            } else {
                $pass_unit = (float) $pass_unit;
            }

            $services   = self::services_addon_total($post_id, $meta['services'] ?? array(), $spots);
            $line_list  = round(($list_unit * $spots) + $services, 2);
            $line_pass  = round(($pass_unit * $spots) + $services, 2);
            $unit       = $pass_applied ? $pass_unit : $list_unit;
            $line_total = $pass_applied ? $line_pass : $line_list;

            $list_total += $line_list;
            $pass_total += $line_pass;

            $lines[] = array(
                'post_id'    => $post_id,
                'unit'       => $unit,
                'list_unit'  => $list_unit,
                'pass_unit'  => $pass_unit,
                'services'   => $services,
                'line_total' => $line_total,
                'list_total' => $line_list,
                'pass_total' => $line_pass,
            );
        }

        $list_total = round($list_total, 2);
        $pass_total = round($pass_total, 2);
        $total      = $pass_applied ? $pass_total : $list_total;
        $discount   = $pass_applied ? max(0, round($list_total - $pass_total, 2)) : 0.0;

        return array(
            'spots'           => $spots,
            'workshop_count'  => $workshop_count,
            'pass_applied'    => $pass_applied,
            'pass_min'        => $pass_min,
            'list_total'      => $list_total,
            'pass_total'      => $pass_total,
            'discount'        => $discount,
            'total'           => $total,
            'lines'           => $lines,
        );
    }

    public static function get_optional_services($post_id) {
        return DLab_Workshop::get_optional_services($post_id);
    }

    public static function service_key(array $row) {
        $slug  = trim((string) ($row['slug'] ?? ''));
        $label = trim((string) ($row['label'] ?? ''));
        return self::service_key_from_value($slug !== '' ? $slug : $label);
    }

    public static function service_key_from_value($value) {
        $value = (string) $value;
        $value = preg_replace('/[\x{00A0}\x{202F}\x{2007}\x{2060}]/u', ' ', $value);
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return '';
        }
        $key = sanitize_title($value);
        return $key !== '' ? $key : $value;
    }

    public static function selected_service_keys(array $selected_slugs) {
        $keys = array();
        foreach ($selected_slugs as $value) {
            $key = self::service_key_from_value($value);
            if ($key !== '') {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    public static function services_addon_total($post_id, array $selected_slugs, $spots) {
        if (empty($selected_slugs)) {
            return 0.0;
        }
        $rows = self::get_optional_services($post_id);
        if (empty($rows)) {
            return 0.0;
        }
        $selected_keys = array();
        foreach ($selected_slugs as $value) {
            $key = self::service_key_from_value($value);
            if ($key !== '') {
                $selected_keys[$key] = true;
            }
        }
        if (empty($selected_keys)) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = self::service_key($row);
            if ($key !== '' && isset($selected_keys[$key])) {
                $total += (float) ($row['price_addon'] ?? 0) * max(1, (int) $spots);
            }
        }
        return $total;
    }
}
