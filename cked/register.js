/**
 * CK Editor plugin registration
 * 
 * Copyright 2017 - TDSystem Beratung & Training  - Thomas Dausner (aka dausi)
 */
if (typeof CKEDITOR !== 'undefined') {
	CKEDITOR.plugins.addExternal('tds_z_i_p_gallery', CCM_REL + '/packages/tds_z_i_p_gallery/cked/');
}