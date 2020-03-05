stores.csv

site_code - always required. If the site_code exists it will update the site with any additional information.  If it is a new site code, it will create a new site. Website code may only contain letters (a-z), numbers (0-9) or underscore (_), and the first character must be a letter.

site_name - required when updating a site name, or creating a new site

site_order - optional, default is zero

is_default_site	- optional: value = Y. There can only be one default site. If it is defined muliple times, the last site updated will be the default. Default cannot be removed, only added to an existing or new site

store_code - required when updating or adding a store or view. Store code may only contain letters (a-z), numbers (0-9) or underscore (_), and the first character must be a letter.

store_name- - required when updating a store name, or creating a store

store_root_category	- required when creating a new store. If the root category given does not exist, one will be created and set to the store.

is_default_store optional: value = Y. There can only be one default store per site. If it is defined muliple times, the last store updated will be the default. Default cannot be removed, only added to an existing or new site
//TODO: check if default store is automatically set on single store site
	
view_code - required when updating or adding a view. View code may only contain letters (a-z), numbers (0-9) or underscore (_), and the first character must be a letter.

view_name - required when updating a view name, or creating a view

is_default_view //TODO: same verification as in store

view_order - optional, default is zero

view_status - optional:enabled or disabled - default is enabled
