/** Show/Hide "Field Separator and Text Qualifier" section. */
var use_custom_csv_settings = jQuery('[name=uewm_use_custom_csv_settings][type="checkbox"]');
use_custom_csv_settings.on('click', update_custom_csv_settings);
function update_custom_csv_settings() {
    var is_checked = use_custom_csv_settings.is(':checked');
    jQuery('#uewm_custom_csv_settings,#uewm_custom_csv_settings + table').toggle(is_checked);
}
update_custom_csv_settings();

/** Show/Hide custom separator fields. */
var field_separator = jQuery('[name=uewm_field_separator]');
field_separator.on('change', update_field_separator);
function update_field_separator() {
    var value = field_separator.val();
    var parent_tr = jQuery('[name=uewm_custom_field_separator]').closest('tr').find('.select-with-text-wrapper');
    parent_tr.toggle(value == 'custom');
}
update_field_separator();

/** Show/Hide custom qualifier fields. */
var text_qualifier = jQuery('[name=uewm_text_qualifier]');
text_qualifier.on('change', update_text_qualifier);
function update_text_qualifier() {
    var value = text_qualifier.val();
    var parent_tr = jQuery('[name=uewm_custom_text_qualifier]').closest('tr').find('.select-with-text-wrapper');
    parent_tr.toggle(value == 'custom');
}
update_text_qualifier();
