<?php
/**
 * Storno or privacy statement from settings.
 *
 * @var string $dlab_legal storno|privacy
 */

if (!defined('ABSPATH')) {
    exit;
}

$key  = (isset($dlab_legal) && $dlab_legal === 'privacy') ? 'privacy' : 'storno';
$html = $key === 'privacy' ? DLab_Settings::legal_privacy_html() : DLab_Settings::legal_storno_html();
if ($html === '') {
    return;
}

$label = $key === 'privacy'
    ? __('Ochrana osobních údajů', 'design-lab')
    : __('Storno', 'design-lab');
?>
<div class="dlab-legal dlab-legal--<?php echo esc_attr($key); ?>">
    <h4 class="dlab-legal__label"><?php echo esc_html($label); ?></h4>
    <div class="dlab-legal__body"><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
</div>
