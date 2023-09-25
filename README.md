# Magento 2 Data Install Module

The Data Install module facilitates the loading of sample data by a series of generic .csv files, or the .json results from GraphQL queries (see the [Data Installer GraphQL ](https://github.com/PMET-public/module-data-install-graphql)module ). This allows for the easy creation/editing of data sets for particular scenerios. It also helps facilitate packaging of demos or verticals so they can be added to an existing site with demo data, or an empty site.

**As the Data Install supports B2B, at this time the B2B modules are required in Magento even if B2B features will not be used.**

## Data Pack Sources
- Commerce Module - This is created like any other Adobe Commerce Module and can be added to the instance via the composer.json file, or under `app/code/Namespace/Module`. The Data Pack can then be referred to by its name `Namespace_Module` when being installed
- File System - The Data Pack directory can be placed under any directory under the Commerce app.
- Zip file - A Data Pack can be packaged as a .zip file and uploaded via the UI. The data and media (images) can be uploaded as separate .zip files via the Commerce UI
- GitHub Remote - The Data Pack can be retrieved from a GitHub repository via the same link used to download a zipped repository e.g. `https://github.com/PMET-public/vertical-data-luma/archive/refs/heads/main.zip`
- When using GitHub Remote to access a private repository, you will need to create a [Personal Access Token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token). This token can be used as an option for the the CLI or GraphQL mutation. Or it can be added to the Commerce instance under *Stores->Configuration->Advanced->System->Data Installer Authorization*. 
  - When setting up the token, create a "classic" token, and check the box to give access to the `repo` scope

## Installation Methods


### CLI

`bin/magento gxd:datainstall <module name, path to data pack, or remote url>`
Optional arguments:
`--load[=LOAD]  Data subdirectory to load if there is more than one`
 `--files[=FILES] Comma delimited list of individual files to load`
 `-r Force Reload`
 `--remote=1 Flag if Data Pack is being retrieved via URL`
 `--authtoken [=AUTHTOKEN] GitHub Personal Access Token`
 `--host [=HOST] Override of host values in stores.csv file`
 `--make-default-website The datapack's new website will be set as the default site`

###### Usage Examples

- `bin/magento gxd:datainstall MySpace_MyData`
Install data from the `MySpace_MyData` module. This module can reside in either *vendor* or *app/code*

- `bin/magento gxd:datainstall var/import/importdata/MyData`
Install data from any directory under the Magento root.  In this case `var/import/importdata/MyData`. This does not need to be a Magento module, but only needs to contain the .csv files and media

- `bin/magento gxd:datainstall MySpace_MyData --load=store1`
Use a specific directory for the .csv files This would allow you to potentially have multiple data sets in the same module *data*,*data2*,*store1*, etc.

- `bin/magento gxd:datainstall MySpace_MyData --files=customers.csv,pages.csv`
Mostly used for testing.  You can pass a comma delimited list specific files you want loaded rather than loading everything

- `bin/magento gxd:datainstall MySpace_MyData -r` Each data directory(`load` value) is logged and will only install once. The `-r` option allows you to reinstall an existing data pack.  If you are going to reinstall, it is a good idea to clear the cache first.  Some configurations are retained in cache, and you may see errors around stores not being found or gxd namespace errors if you reinstall without clearing the cache.

- If you need to install multiple data packs at the same time, you can chain commands together:`bin/magento gxd:datainstall MySpace_MyData;bin/magento gxd:datainstall MySpace_MyData2;bin/magento gxd:datainstall MySpace_MyData3`


### Upload Via UI

A .zip file can be created with assetts that follow the Data Pack format. It is then uploaded via the Commerce UI at *System->Data Transfer->Import Data Pack*

If you have extracted data and images via the GraphQL queries, those assets can be merged into one Data Pack. Or the resulting .zip file from the `imagesExtract` can be loaded separatly. 

Advanced Conditions the same as used by the CLI can be added as needed. When uploaded a job is created to import the data pack. The `magentoese_datainstall.import` consumer will install the Data Pack in the background.

Depending on the size of the Data Pack, you will need to make sure your server is configured to allow a larger file upload.  You can check the server setting by going to *System->Import* in the Commerce admin. There will be a message at the top of the page that will read something like - "Make sure your file isn't more than 28M". That indicates the maxium size of file that can be uploaded. This can be increased by setting two php variables to the appropriate size
Example :
`post_max_size = 128M`
`upload_max_filesize = 128M`

### GitHub Remote Via UI
In the same UI used to upload a zip file, you can also enter a GitHub URL to remotely retrieve the Data Pack. Advanced Conditions the same as used by the CLI can be added as needed. After the Data Pack is retrieved, a job is created to schedule the import. The `magentoese_datainstall.import` consumer will install the Data Pack in the background.
If you are accessing private repos, a GitHub Personal Access Token will need to be added and included in the Commerce store configuration under *Stores->Configuration->Advanced->System->Data Installer Authorization*. It could also be included as part of the Advanced Conditions.

### GraphQL Scheduled
A GraphQL mutation can be used to mimic the same functionality that the CLI provides. This would include installing an existing Data Pack or GitHub Remote. Additional queries can also return job status and logging information. See the [Data Installer GraphQL ](https://github.com/PMET-public/module-data-install-graphql)module for information

### Running Scheduled Imports.
Data Packs loaded via the UI or GraphQL will be imporated by a bulk job run by a consumer. The consumer can be started manually by `bin/magento queue:consumers:start magentoese_datainstall.import`

## Datapack data format

1. Each datapack needs to have a `data` subdirectory. This can contain the data to install, or it can contain one or more subdirectories with different installation options.  For example, in our Grocery data pack there are directories for a `standalone` or `store` installation. Those can be specified by using the `--load=` option.

2. A `.default` file can be created under the `data` directory containing the name of the directory you want installed if none is specified with the `--load=` option.

3. The `media` directory is used for images and other assets. Most items are copied to their correct destinations under `pub/media` with the exception of product assets which read by the importer. Supported sub-directories include:
   * `catalog` - Mostly contains product images under the `product` subdirectory. From there any image path should follow what is expected in the product import file.
   * `downloadable_products` - downloadable product assets to be assigned during product import
   * `favicon` - As defined in the config files
   * `logo` - As defined in the config files
   * `template_manager` - Images used in `templates.csv`
   * `wysiwyg` - CMS images. It is recommended that they be placed in a unique subdirectory to make it cleaner in the UI if multiple data packs are loaded. The path needs to match what is referenced in CMS items.
   * `email` - email logo as defined in config files
   * `theme` - If a theme is used in a vertical, it can be added here. All the direcories and files used in creating a Commerce theme are placed here and will be copied to `app/design/frontend`

4. Only the `data` and `media` directories are required. If you are going to be referring to the Data Pack as a commerce module, you will then need to include the rest of the standard module files.
5. If you are zipping a Data Pack for upload via the Commerce interface, the .zip file should include the entire folder and its contents. Just zipping the folder contents will generate an error on import.
6. The theme contained within the Data Pack will not work on Commerce Cloud due to the read-only file system. A theme will need to be added via composer. If the Data Pack is used across multiple plaforms, the Data Pack theme and the composer theme will need to have different path names, and will need to be addressed by using the `fallback_theme` in the stores file

Sample Data Module - [https://github.com/PMET-public/module-storystore-sample](https://github.com/PMET-public/module-storystore-sample "https://github.com/PMET-public/module-storystore-sample")

### Handling of the default `base` website and additional websites

When Adobe Commerce installs, a default web site is created with a site code of `base`. In order to facilitate the ease of re-use of data packs, most are created assuming that the `base` web site exists. The data installer will make adjustments in the case that the `base` site code has been changed.

If the data that is being installed calls for a `base` website, and that site does not exist, the Data Installer will substitute `base` with the site code of the default web site.

Of course you can always specify any site code where appropriate in the data.

If you have a data pack that creates a new website, you can have the option to have that website render as the default site. This is most useful when installing on an empty instance. This can be accomplished by having two versions of the data for both the `base` webiste and the new website. However to avoid that duplication, you can add the `--make-default-website` option to the installation. This will install the datpack as a new website, but make adjustmets to the base url and default website settings.  So the new datapack will render as the default site.

### Events, Observers and Webhooks

There are two events generated at the start and end of the installation process that can be used by other modules
`magentoese_datainstall_install_start` and `magentoese_datainstall_install_end`

Plugins and observers have been created to integrate with the `mageplaza/module-webhook` extension. As of this writing they are not included as the module is not yet php 8.1 compatible.  When updated, this will allow webhooks to be configured based on the start and end events.

## Data Files

Each element of potential sample data is encapsulated in its own file. Below is the list of .csv files that are supported.  If you are using the results from the supported GraphQL queries, see the [Data Installer GraphQL ](https://github.com/PMET-public/module-data-install-graphql)module )

[**settings.csv**](#Settings) - Optional file containing settings used by the install process.

[**stores.csv**](#Stores) - Used to create sites, stores, store views and root categories

[**Configuration files**](#Configuration) - **config_default.json, config_default.csv, config_vertical.json, config_vertical.csv, config_secret.json, config_secret.csv, config.json, config.csv**. - These files contain settings that would mostly be set in Stores->Configuration: Payment methods, store information, admin settings, etc.  See the [**Configuration files**](#Configuration) section for details on their usage.

[**admin\_roles.csv**](#admin-roles) - Creates roles for Admin users

[**admin\_users.csv**](#admin-users) - Creates Admin users

[**customer\_groups.csv**](#customer-groups) - Creates customer groups

[**customer\_attributes.csv**](#customer-attributes) - Creates customer attributes

[**customer\_segments.csv**](#customer-segments) - Creates customer segments

[**customers.csv**](#customers)  - Creates customers. Also used to add customer data to autofill.

[**customer\_addresses.csv**](#customer-addresses) - Adds address records to customers

[**reward\_exchange\_rate.csv**](#reward-exchange-rate) - Sets the conversion values for Reward Points

[**product_attributes.csv**](#product-attributes) - Creates product attributes and sets

[**categories.csv**](#categories) - Creates categories

[**products.csv**](#products) - Creates simple and configurable products

[**gift\_cards.csv**](#gift-cards) - Updates products of type giftcard due to importer issue

[**msi\_stock.csv**](#msi-stock) - Creates Stock definitions for MSI, and ties the stock to MSI sources

[**msi\_source.csv**](#msi-source) - Creates MSI sources

[**msi\_inventory.csv**](#msi-inventory) - Updates inventory for MSI sources

[**advanced\_pricing.csv**](#advanced-pricing) - Sets tier and group pricing

[**upsells.csv**](#upsells) - Used to create the Related Products rules

[**catalog\_rules.csv**](#catalog-rules) - Used to create the Catalog Promotion Rules

[**cart\_rules.csv**](#cart-rules) - Used to create the Cart Promotion Rules

[**blocks.csv**](#blocks) - Creates Blocks. Includes Page Builder compatibility

[**dynamic_blocks.csv**](#dynamic-blocks) - Creates Dynamic Blocks. Includes Page Builder compatibility

[**widgets.csv**](#widgets) - It is recommended to create widgets in a Magneto store and then extract them via DB query. The data is too complex to create manually. A single layout update per widget is supported at this time.

[**pages.csv**](#pages) - Creates and updates pages. Includes Page Builder compatibility

[**templates.csv**](#templates) - Create Page Builder templates from existing Page Builder content

[**reviews.csv**](#reviews) - Creates reviews and ratings

### B2B Files
[**b2b_approval_rules.csv**](#b2b-approval-rules) - Creates purchase order rules

[**b2b_companies.csv**](#b2b-companies) - Creates companies

[**b2b_company_roles.csv**](#b2b-company-roles) - Creates roles used by customers within companies

[**b2b_customers.csv**](#b2b-customers) - Creates customers within a company

[**b2b_requisition_lists.csv**](#b2b-requisition-lists) - Creates requisition lists for B2B customers

[**b2b_sales_reps.csv**](#b2b-sales-reps) - Creates B2B sales reps

[**b2b_shared_catalog_categories.csv**](#b2b-shared-catalog-categories) - Ties product categories to a shared catalog

[**b2b_shared_catalogs.csv**](#b2b-shared-catalogs) - Creates shared catalog

[**b2b_teams.csv**](#b2b-teams) - Creates company structure and assigns users to teams

[**b2b_negotiated_quotes.json**](#b2b-negotiated-quotes) - Creates negotiable quotes of company

*Not yet supported*
**staging,orders, refunds, credit memos or anything else not listed**

All non B2B files are mostly processed in the order listed as there are some dependencies between files. For example, Catalog Rules depend on Products being loaded.  There are some circular dependencies that may come up (Categories can contain Blocks that can contain Categories). There is a mechanism in place that will process some files twice to solve these issues

B2B files have many interdependencies, so you may have a load rejected if you are missing a file. Or if you try to load a specific B2B file, you may see all B2B files get processed. There are also many data dependencies, for example data may be rejected if references to Company Name, or Company Customers are incorrect

------------

# Files

### Settings

*File Name* - settings.csv

Optional file. This file contains settings used by the install process. This file is optional if you are adding data to a base installation.  It will be used in a multi-store scenario, or if you are going outside of some of the defaults. This will remove the requirement of having to use the included values in other data files.

*Columns* - **name,value**

*Recognized name/value pairs*

**site\_code** - Default : base

**store\_code** - Default : main_website_store

**store\_view\_code** - Default : default

**product\_image\_import\_directory** - Path from server root to directory where images should be read during product import.  Defaults to `<module with data files>\media\products`

**restrict\_products\_from\_views** - (Y/N, Default: Y) Used to set the visibility of products, so products from one store view don't show in the search from another. When installing new products, the visibility for existing products is set to **Not Visible Individually** for the view defined by **store\_view\_code**. Visibility for products added from the products.csv file will be set to **Not Visible Individually** for all views (including *default*) except for the **store\_view\_code** defined in that row of the products.csv data file.

**product\_validation\_strategy** - (validation-stop-on-errors,validation-skip-errors) Default : validation-skip-errors. Setting to either stop a product import on an data error or allow it to continue. This is the Validation Strategy setting in the Import admin UI.

### Stores

*File Name* - stores.csv

Optional file: This file is used to add and update Sites, Stores, Store Views and Root Categories. The codes provided in the file are used to determine if a new element will be created or updated.
> Out of Scope: Updating of Codes and Root Category Name

*Columns*

**site\_code** - Always required. If the site\_code exists it will update the site with the provided information.  If it is a new site code, it will create a new site. Website code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**site\_name** - Required when updating a site name, or creating a new site

**site\_order** - Optional: Default is zero

**store\_code** - Required when updating or adding a store or view. Store code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter. Code will be fixed automatically if needed

**store\_name** - Required when updating a store name, or creating a store

**store\_root\_category** - Optional: Default is the installation Default Category. Needs to be provided if a different Root Category is required. If the Root Category given does not exist, one will be created and assigned to the store.

**is\_default\_store** Optional: Allowed value = Y. There can only be one default store per site. If it is defined multiple times, the last store updated will be the default. The default store cannot be removed from a site, only changed to a different store.

**store\_view\_code** - Required when updating or adding a view. View code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**view\_name** - Required when updating a view name, or creating a view

**is\_default\_view** - Optional: Allowed value = Y. There can only be one default view per store. If it is defined multiple times, the last view updated will be the default. The default view cannot be removed from a store, only changed to a different view.

**view\_order** - Optional: Default is zero

**view\_is\_active** - Optional: values = Y/N. Default = N. If a view is set as default for a store, it cannot be deactivated.

**host** - Optional: Used to set the Base Urls for a site.  There are 3 allowed values

1. The desired domain name. Can use subdomains and subdirectories (example: `luma.com`, `store.luma.com`, `luma.com/canada`).
1. `subdomain` - If you are using different subdomains for website, a base url will be generated based on the `site_code` and  default base url. (example: if the `site_code` is `us` and the default base url is `http://site.test`, the resulting base urls for this site will be `http://us.site.test` & `https://us.site.test` )
1. `subdirectory` - If you are using different subdirectories for website, a base url will be generated based on the `site_code` and  default base url. (example: if the `site_code` is `us` and the default base url is `http://site.test`, the resulting base urls for this site will be `http://site.test/us` & `https://site.test/us` )

This is set at the website level. If it needs to be set for another scope, that can be done in the config.json or config.csv files.

**theme** - Optional: Assigns a theme to the store view. This should be the path of the theme directory from the Vendor namespace. For example Magento/luma or MagentoEse/venia
**theme_fallback** - Optional: Second option to assign a theme to the store view. This would be used in the case that the theme in the `theme` column does not exist

### Configuration

*File Names* - config_default.json, config_default.csv, config_vertical.json, config_vertical.csv, config_secret.json, config_secret.csv, config.json, config.csv

The .json and .csv files are interchangeable. Both formats serve the same purpose, so it is up to personal preference which format is used. All files are optional, but it is recommended to have the default file in order to create the settings to have a reasonably operational store.

These files are used to set values that would normally be set in the store admin under Stores -> Configuration.

The purpose of having the multiple files (default, vertical,secret,config) is to allow the flexibility of having some settings that are likely to never change, or may be applicable to a specific vertical, and then override them with subsequent files.

##### File Processing Order

**config_default.json, config_default.csv** - These files would likely be included in a base data pack, and would likely not be changed.  It would include common admin settings like security settings, basic payment and shipping settings, etc. These files would contain the basic settings to get a site up and running, and would likely not be changed.

**config_vertical.json, config_vertical.csv** - Adds to or overrides settings in the default files. These would normally be things that are applicable to the specific data set like Venia or Luma German, or Grocery. Generally should not be edited by the end user if they are loading a prepared data set

**config_secret.json, config_secret.csv** - This file could contain private information like API keys, passwords, etc.  That information is not required to go into this file, and could go in any configuration file. However it should be a best practice in order to easily remove private information if publicly distributing the rest of the data pack

**config.json, config.csv** - These files are final files loaded and are used to add to or override any previous settings.  This is likely where an end user would make changes specific to their needs.

*Json file format* - This structure has nodes matching the path of the variable to set

*Handling Encryption* - There are some values that need to be encrypted by Magento before they are stored (passwords,API keys, etc.). Those unencrypted values can be placed within an `encrypt()` tag. For example, in a json file `"public_key": "encrypt(2mafn9s8fjae89)",`

*CSV file Columns*  - the file format matches the values stored in the core\_config\_data table

**path** - Required. Path matching values set in the core\_config\_data table e.g. `general/locale/code`
**value** Required. Value to set
**scope** - Optional. Allowed scopes are `websites`, `stores`, `default`. Defaults to `default`.
**scope\_code** - Required if scope is `websites` or `stores`. Include the scope_code of the site or store you want the value set for

### Admin Roles

*File Name* - admin\_roles.csv

Optional file: Creates roles and their settings for admin users. Updates are done for the whole role, not incrementally by row

*Columns*
**role** - Required. Name of the role
**resource_id** - Resource to activate for the role (eg: `Magento_Backend::admin`) one per row

Resource Ids can be obtained by creating a role in the UI and retrieving the information from the `authorization_rule` table.
`select a.role_name as 'role', b.resource_id 
from authorization_role a, authorization_rule b
where a.role_id = b.role_id
and a.role_name = '...'
and b.permission = 'allow'`

If you are adding a role with access to All, you only need one row

|role|resource_id|
| --- | ----------- |
|Sales Admin|Administrators	Magento_Backend::all|

### Admin Users

*File Name* - admin\_users.csv

Optional file: Creates users for the Magento Admin.

*Columns*
**email** - Required. Key used for updates
**username** - Required.
**firstname** - Required.
**lastname** - Required.
**password** - Required.
**role** - Optional. Needs to be an existing role

### Customer Groups

*File Name* - customer\_groups.csv

Used to create customer groups
> Out of Scope: Renaming customer groups. Assigning Tax Class.

*Column*
**name** - Required. Name of the customer group

### Customer Attributes

*File Name* - customer\_attributes.csv

This file is used to add and update customer attributes
Customer attribute configurations can be complex. The purpose of this file is to address the most common settings. The file can be generated manually or from a series of database queries on an configured instance

*Get Attribute information*
`select eav.attribute_code, eav.frontend_input, eav.frontend_label,
case when eav.is_required = 0 then 'N' else 'Y' end as is_required,
case when ca.is_used_in_grid = 0 then 'N' else 'Y' end as is_used_in_grid,
case when ca.is_visible_in_grid = 0 then 'N' else 'Y' end as is_visible_in_grid,
case when ca.is_filterable_in_grid = 0 then 'N' else 'Y' end as is_filterable_in_grid,
case when ca.is_searchable_in_grid = 0 then 'N' else 'Y' end as is_searchable_in_grid,
case when ca.is_used_for_customer_segment = 0 then 'N' else 'Y' end as is_used_for_customer_segment,
ca.sort_order
from eav_attribute eav
inner join customer_eav_attribute ca
on eav.attribute_id = ca.attribute_id
where eav.attribute_code in ('...','...')`

*Get value for `options` column for select or multi-part*
`select group_concat(ov.value order by op.sort_order separator '\n') as options
from eav_attribute_option_value ov
left join eav_attribute_option op
on ov.option_id = op.option_id
where op.attribute_id in (select attribute_id from eav_attribute where attribute_code = '...')`

*Get value for `use_in_forms` column*
`select group_concat(fa.form_code separator ',') as use_in_forms
from customer_form_attribute fa
left join eav_attribute eav
on fa.attribute_id = eav.attribute_id
where eav.attribute_code = '...'`

> Out of Scope: Updating Attribute codes. Store scope labels. Website scope. Any attribute setting not currently listed

*Columns*

**attribute\_code** - Always required. If the attribute\_code exists it will update the attribute with the provided information.  If it is a new code, it will create a new attribute. Attribute code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**frontend\_label** - Required when creating a new attribute.

**frontend\_input** - Required when creating a new attribute. Catalog Input Type for Store Owner. Allowed values are text, textarea, texteditor, date, boolean, multiselect, price

**is\_required** - Optional: Values = Y/N. Default = N

**options** - Required when input is Multi or Select. Values to show, carriage return delimited. Value and label will be the same

**sort_order** - Optional, Numeric, defaults to 100.  Indicates the position of the attribute within the Attribute Group

**is_used_in_grid** - Optional, Values = Y/N. Default = Y.  Add to list of column options in the customer grid

**is_filterable_in_grid** - Optional, Values = Y/N. Default = Y.  Can it be used in grid filters

**is_searchable_in_grid** - Optional, Values = Y/N. Default = Y.  Can it be used in grid search

**is_used_for_customer_segment** - Optional, Values = Y/N. Default = Y.  Can it be used to create customer segments

**use_in_forms** - Optional, Values = `adminhtml_customer,adminhtml_checkout,customer_account_edit,customer_account_create`. Default = all forms.  Comma delimited list of forms where the attribute can be defined

### Customer Segments

*File Name* - customer\_segments.csv

This file is used to add and update customer segments.
Because segments are complex, the best method is to create a segment in a test environment and then export that data out of the database to put in the .csv file. You will need to use token substitution in the `conditions_serialized` if there are required references to ids.
`select name, '...' as site_code, description, is_active,apply_to,conditions_serialized
from magento_customersegment_segment
where name in '...'`

*Columns*

**name** - Always required. Name of the segment shown in the UI and also the key used for updates.

**site\_code** - Optional. Single site_code or comma delimited list for multiple sites. Will take the value from settings.csv if not provided.

**description** - Optional. Description of the segment

**is\_active** - Optional: Values = 1/0. Default = 1

**apply\_to** - Optional. Defaults to 0.
Accepted values 2= Apply to Visitors, 1= Apply to Registered Users, 0= Both Visitors and Registered

**conditions_serialized** - Optional (but if you dont put anything in, then really whats the point?) - This is taken from the database magento_customersegment_segment.conditions_serialized column. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers like product and customer attributes.  ID values for Region and Country should remain in place as they should be consistant across installations.

### Customers

*File Name* - customers.csv

Optional file: Used to create customers

There are multiple file formats that can be used for importing customers. The Magento exporter supports Customer Main File, which doesn't include any address information, and Customer Addresses which include all defined addresses.  There is no export that includes a composite Customers and Addresses file that is supported by the importer. If you are exporting files you can leave them as two separate files, or combine them into a single customer file. The single customer file method will only support one address for both billing and shipping.

If you are using an export, make sure you have the correct website, store and group values for your data.  If website or store are not included, it will use the defaults in `settings.csv`. You will also need to remove any customer attribute columns that aren't needed, and to clean up any other columns that aren't needed like **created_at**, **updated_at**.  Your file should just include customer profile information, store/site/group information, customer profile and attribute values.

If you are importing the addresses separately, they will need to go into the `customer_addresses.csv` file, which is detailed in its own section

The customer file will use the same file format as the native Magento customer import with some exceptions:
**add_to_autofill** - Optional.  This will add the customer as a selectable option to the [Autofill Module](https://github.com/PMET-public/module-autofill "Autofill Module")
**group** - Optional.  Name of the customer group.  If not defined, default will be `General`. **group_id** can be used but the id must exist in the imported instance
**reward_points** - Optional.  This will set set the number of Reward Points for a customer. The conversion rates are defined in `reward_exchange_rate.csv` but are not needed to add points to a customer.

Some column names may have alaises for ease of use and consistency with other Data Installer data files.
**site_code** if it exists it will populate the **_website** value
**store_view_code** if it exists it will populate the **_store** value
**group** if it exists it will convert to an ID and populate the **group_id** value.
**_address_firstname** and **_address_firstname** in a composite Customers and Addresses file these are optional. **firstname** and **lastname** will be substituted if they are not defined.

At this time, only one address is supported and used for both billing and shipping.  However, new addresses can be added from a second module, essentially updating the customer but adding, not replacing addresses.  Last address in gets set as default.

If you are getting errors while importing customers, you can try importing it via the admin UI to get better error feedback. Or in **settings.csv** add `product_validation_strategy,validation-stop-on-errors`. This will set the Allowed Error Count to 0 and give you better error output.

### Customer Addresses

*File Name* - customer_addresses.csv

Optional file: Used to add addresses to customers

It is recommended to use an exported customer address file. Make sure you have the correct `_website` populated, or remove the `_website` column. If `_website` is not included, it will use the defaults in `settings.csv`. `_entity_id` is a column required by the importer. However, it can be problematic as it is taking ids from the original instance. You can leave that column out of your file. Or, the Data Importer will clear out the values if it exists.

*note that updating addresses is not supported. If you import the same address file multiple times, the addresses will be added each time.

### Reward Exchange Rate

*File Name* - reward_exchange_rate.csv

Optional file: Used to set Reward Point conversion rate

Only one rate per website,customer group and direction is allowed. Existing rates will be updated using that key

*Columns*
All columns are required

**site_code** - Optional: Website code, will default to value in `settings.csv` if not included
**customer_group** - Optional. Name of Customer Group or `all`, will default to `all` if not included
**direction** - Values: `points_to_currency` or `currency_to_points`
**points** 
**currency_amount** 

### Categories

*File Name* - categories.csv

This file is used to create categories. Categories are also created during product import so this file may be optional. It can be used if you want control over position and visibility.


*Columns*

**name** - Required. This is what will show on the storefront

**url\_key** - Required. Needs to be unique

**path** - If left empty, the category will be top level. Subcategories will need to have their parents listed. For example

- Women (no path)
      - - - Tops (Path = Women)
            - - - Sweaters (Path = Women/Tops)
            - - - Jackets (Path = Women/Tops)

Parent categories need to be in the file before the child categories

**is_active** - Optional: Values = 1/0. Default = 1

**is\_anchor** - Optional: Values = 1/0. Default = 1

**include\_in\_menu** - Optional: Values = 1/0. Default = 1

**position** - Optional, Numeric.  Indicates the position of the category within its specific branch

**description** - Optional.  Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers for Page Builder compatibility

**display_mode** - Optional. Default=PRODUCTS. Allowed values: `PRODUCTS`, `PAGE`, `PRODUCTS_AND_PAGE`

**landing_page** - Required when display mode is `PAGE` or `PRODUCTS_AND_PAGE`, identifier of block to include

**custom_design** - Required when setting page layout.  Theme namespace/name. For example Magento/luma

**page_layout** - Optional. Based on values selectable from the UI
| UI selection | Value |
| --- | ----------- |
| Empty | empty |
| 2 Columns with left bar | 2column-left |
| 2 Columns with right bar | 2column-right |
| 3 Columns | 3columns |
| Page -- Full Width | cms-full-width |
| Category -- Full Width | category-full-width |
| Product -- Full Width | product-full-width |

### Products

*File Name* - products.csv

The standard Magento Product import file is used. If you export from an existing store, you may need to make the following adjustments:

- Change any store codes, website, attribute set or category definitions to match your new configuration
- Image references will be the path of the final image *example:* `i/m/image.jpg`. Those will need to be updated to the path of your import source.  Most likely the path will be removed and just the file name (`image.jpg`) will be used

### Gift Cards

*File Name* - gift_cards.csv

When Gift Cards are imported along with other products, the row will be skipped with an error. Gift cards need to be imported separatly.

The file  should be generated with the same method as `products.csv`, but should only include the Gift Card products. You do not need to remove gift cards from the original products file

### MSI Source

*File Name* - msi_source.csv

To extract from the database:`select s.source_code, s.name, s.enabled,ifnull(s.description,'') as description,
ifnull(s.latitude,'') as latitude,ifnull(s.longitude,'') as longitude,
ifnull(s.region_id,'') as region_id,s.country_id, ifnull(s.city,'') as city,ifnull(s.street,'') as street,ifnull(s.postcode,'') as postcode,ifnull(s.contact_name,'') as contact_name,
ifnull(s.email,'') as email,ifnull(s.phone,'') as phone,ifnull(s.fax,'') as fax,
s.use_default_carrier_config,s.is_pickup_location_active,ifnull(s.frontend_name,'') as frontend_name,ifnull(s.frontend_description,'') as frontend_description
from inventory_source s
where  s.source_code <> 'default'`

The list of required fields depends on the configuration of in store pickup, so it is easiest to create the file by query vs. creating it manually

Updates can be made with `source_code` as key.

### MSI Stock

*File Name* - msi_stock.csv

To extract from the database:`select st.name as stock_name, ifnull(sc.code,'') as site_code,ifnull(GROUP_CONCAT(sl.source_code order by sl.priority),'') as source_code
from inventory_stock st
left outer join inventory_stock_sales_channel sc
on st.stock_id = sc.stock_id
left outer join inventory_source_stock_link sl
on st.stock_id = sl.stock_id
where st.name <> 'Default Stock'
group by sc.code, st.name`
This query will only return one site per stock. If you have multiple sites defined for a stock, the file will need to be edited.

Stock names cannot be updated, but the website and source assignments can be using the `stock_name` as key.

*Columns*
**stock\_name** - (required)

**site\_code** - (optional) A comma delimited list of websites to assign the Stock to.  A website can only be assigned to one Stock, so if a website is listed multiple times it will be assigned to the last Stock in the file. If this column is empty, the Stock will be created but not assigned to a website

**source\_code** - (optional) A comma delimited list of source to assign to the stock.

### MSI Inventory

*File Name* - msi_inventory.csv

The standard Magento Stock Sources import file is used and can be exported from a configured instance
Due to issues with MSI Inventory not being able to be updated in the same command as a product import, the `gxd:datainstall` command needs to be run a second time, where you can limit it just to the MSI Inventory file
`bin/magento gxd:datainstall MySpace_MyData --files=msi_inventory.csv -r`

*Columns*
**source\_code** - MSI Source

**sku** - Product sku

**qty** - Inventory to assign to source

**status** - Values (1/0) - sets if the product will be listed as In Stock for the source

### Advanced Pricing

*File Name* - advanced\_pricing.csv

The standard Magento Advanced Pricing import file is used. If you export from an existing store, you may need to make adjustments in websites or groups

### Product Attributes

*File Name* - product\_attributes.csv

This file is used to add and update Product Attributes and assign them to attribute sets. The codes provided in the file are used to determine if a new attribute will be created or updated.
Product attribute configurations can be complex. The columns examples below are a bare minimum needed if you were createing the file manually.  It's recommended to do a database extract.All settings are supported using database column names and values.

*Note: There is a very specific known bug. If you re-load a product attribute file with an attribute type of text swatch, the option values will be duplicated. You will then need to remove the duplicate options*

Query to extract attributes
`select '' as attribute_set, eav.attribute_code, eav.frontend_input, eav.frontend_label,
ca.is_visible,ca.is_searchable,ca.is_filterable,ca.is_comparable,ca.is_visible_on_front,ca.is_html_allowed_on_front,ca.is_used_for_price_rules,
ca.is_filterable_in_search, ca.used_in_product_listing,ca.used_for_sort_by,ifnull(ca.apply_to,'') as apply_to,ca.is_visible_in_advanced_search,ca.position,
ca.is_wysiwyg_enabled,ca.is_used_for_promo_rules,ca.is_required_in_admin_store,ca.is_used_in_grid,ca.is_visible_in_grid,
ca.is_filterable_in_grid,ca.search_weight,ca.is_pagebuilder_enabled,ifnull(ca.additional_data,'') as additional_data,'' as 'option'
from eav_attribute eav
inner join catalog_eav_attribute ca on eav.attribute_id = ca.attribute_id where eav.attribute_code in('...','...')`

The queries to extract option values need to be run individually for each attribute with the results placed in the `option` column of the main attribute file.

Get options or simple text swatch
`select group_concat(ov.value order by op.sort_order separator '\n') as 'option' from eav_attribute_option_value ov
left join eav_attribute_option op on ov.option_id = op.option_id where op.attribute_id in (select attribute_id from eav_attribute where attribute_code = '...')
order by op.option_id`

Get Text Swatches with different description value
`select group_concat(concat(os.value,'|',ov.value) order by op.sort_order separator '\n') as 'option' from eav_attribute_option_swatch os
left join eav_attribute_option op on os.option_id = op.option_id
left join eav_attribute_option_value ov on os.option_id = ov.option_id
where op.attribute_id in (select attribute_id from eav_attribute where attribute_code = '...')
and ov.store_id = 0
order by op.option_id`

Get Color Swatches
`select group_concat(concat(ov.value,'|',os.value) order by op.sort_order separator '\n') as 'option' from eav_attribute_option_swatch os
left join eav_attribute_option op on os.option_id = op.option_id
left join eav_attribute_option_value ov on os.option_id = ov.option_id
where op.attribute_id in (select attribute_id from eav_attribute where attribute_code = '...')
and ov.store_id = 0
order by op.option_id`

> Out of Scope: Image based swatches

*Columns*

**attribute\_code** - Always required. If the attribute\_code exists it will update the attribute with the provided information.  If it is a new code, it will create a new attribute. Attribute code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**frontend\_label** - Required when creating a new attribute or adding a label translation

**frontend\_input** - Required. Catalog Input Type for Store Owner. Allowed values are text, textarea, texteditor, date, boolean, multiselect, select, price

**is\_required** - Optional: Values = Y/N. Default = N

**option** - Required when input is Multi or Select or Swatch. Values to show, carriage return delimited. In the case of color swatches a pipe delimited value of label|color is used (`Green|#32faaa`). For text swatches if the value and description are different, a pipe delimited value of value|description is used (`SM|Small`). If the description is not needed, the single value of `Small` can be used. See below notes on adding store specific labels

**additional\_data** - Required if attribute is using swatches. This is what will determine if swatches are used. It is recommended to use the db extract. The json data is simple though and is used to configure swatches - example `{"swatch_input_type":"visual","update_product_preview_image":"0","use_product_image_for_swatch":"0"}`

**position** - Optional, Numeric.  Indicates the position of the attribute within the Attribute Group

**attribute\_set** - Optional. Carriage return delimited list of Attribute Sets that the Attribute will be added to.  Sets will be created as needed based on the Default set. If no value is given, the Attribute will be added to the Default set.

**only\_update\_sets** - Optional Value=Y. Only requires attribute\_code. This would be flagged in the case where the only action is to add an attribute to a set.  Most likely usage would be for assigning default system attributes to a set.

*Translating Front End Labels*
After the attributes are created (usually in the admin scope), the translation of front end labels for additional stores can be added with a simpler file.

frontend_label,store_view_code,option,attribute_code,frontend_input

**store\_view\_code** - Required otherwise will set the admin label

**attribute\_code** - Required

**frontend\_label** - Required

**frontend\_input** - Required. This should match what the attribute was created as

**option** - Required for select type attributes.  Values should be carriage return delimited with tilde delimited key~value pairs for the translations. (e.g Luggage~Equipaje). The key should match the option value set during the creation of the attribute.

### Upsells

*File Name* - upsells.csv

This file is used to add Related Products rules. At this time, the easiest method is to create the rules in an existing store, then take the serialized values from the database
`select name, if(is_active=1,'Y','N') as is_active,conditions_serialized,actions_serialized,positions_limit,
case apply_to WHEN 1 THEN 'related' WHEN 2 THEN 'upsell' WHEN 3 THEN 'crosssell' end apply_to,sort_order
from magento_targetrule where name in '...'`

*Columns*

**name** - Required. Used as key to update existing rules

**is\_active** - Y or N, Default = Y

**conditions\_serialized** - Value taken from the`conditions_serialized` column in the `targetrule` table. Content is run through the [**Content Substitution**](#content-substitution) process that will replace identifiers, most likely category ids. Make sure to double quotes for csv file compatibility.

**actions\_serialized** - Value taken from the`actions_serialized` column in the `targetrule` table. Content is run through the [**Content Substitution**](#content-substitution) process that will replace identifiers, most likely category ids. Make sure to double quotes for csv file compatibility.

**positions\_limit** - Numeric. Max number of products to display

**apply to** - Values: related, upsell, crosssell

**sort_order** - Numeric

**customer_segments** - Optional, defaults to all. Comma delimited list of Customer Segment names to apply the rule to

### Catalog Rules

*File Name* - catalog\_rules.csv

This file is used to add and update catalog promotion rules.
Because rule definitions are complex, the method currently in use is to create a catalog rule in a test environment and then export that data out of the database to put in the .csv file

`select '' as site_code, '' as customer_groups,name,description,if(is_active=1,'Y','N') as is_active,conditions_serialized,actions_serialized,
if(stop_rules_processing=1,'Y','N') as stop_rules_processing,sort_order,simple_action,discount_amount,'' as dynamic_blocks
from catalogrule where name in ('...')`

After extraction `site_code`, `customer_groups` and `dynamic_blocks` will need to be filled out.  `actions_serialized` and `conditions_serialized` will need to have content substitution tokens added where necessary



*Columns*

**name** - Always required. Name of the rule shown in the UI and also the key used for updates.

**site\_code** - Optional. Single site_code or comma delimited list for multiple sites. Will take the value from settings.csv if not provided.

**description** - Optional. Description of the rule

**is\_active** - Optional: Values = Y/N. Default = Y

**conditions_serialized** - Optional (but if you dont put anything in, then really whats the point?) - This is taken from the database catalogrule.conditions_serialized column. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers like product attributes.

**actions_serialized** - Optional (but if you dont put anything in, then really whats the point?) - This is taken from the database catalogrule.conditions_serialized column. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers like product attributes.

**stop\_rules\_processing** - In the UI: *Discard subsequent rules*
Optional: Values = Y/N. Default = N

**sort\_order** - In the UI: *Priority*
Optional: Default = 0

**simple\_action** - In the UI: *Apply* Required
| Value in UI                           | Value in File |
| ------------------------------------- | ------------- |
| Apply as percentage of original       | by_percent    |
| Apply as fixed amount                 | by_fixed      |
| Adjust final price to this percentage | to_percent    |
| Adjust final price to discount value  | to_fixed      |

**discount\_amount** - Required: Numeric value

**customer\_groups** - Optional: Defaults to NOT LOGGED IN and General
Single customer group name or comma delimited list

**dynamic\_blocks** - In the UI: *Related Dynamic Blocks*
Optional: Single dynamic block name  or comma delimited list

### Cart Rules

*File Name* - cart\_rules.csv

This file is used to add and update cart promotion rules.

At this point Automatically generated coupon codes are not supported. If you do use a specific coupon code, you need to insure that it is not used by another Cart Rule, or the row will be rejected. Also labels and store specific labels are not supported.

Because rule definitions are complex, the method currently in use is to create a cart rule in a test environment and then export that data out of the database using this query:
`select '' as 'site_code','' as 'customer_group',r.name, r.description, r.uses_per_customer,r.is_active,r.conditions_serialized,r.actions_serialized,r.stop_rules_processing,r.is_advanced,r.sort_order,r.simple_action,r.discount_amount,r.discount_qty,r.discount_step,r.apply_to_shipping,r.times_used,r.is_rss,r.coupon_type,r.use_auto_generation,r.uses_per_coupon,r.simple_free_shipping,IFNULL(c.code,'') as 'coupon_code', rw.points_delta as 'reward_points_delta'
from salesrule r
left join salesrule_coupon c
on r.rule_id = c.rule_id
left join magento_reward_salesrule rw
on r.rule_id = rw.rule_id`

*After Extraction Edits*

**site\_code** - Optional. Single site_code or comma delimited list for multiple sites. Will take the value from settings.csv if not provided.

**customer\_group** - Optional. Comma delimited names of Customer Groups. If left empty, or if the value of `all` is used, the rule will be available to all customer groups

**conditions_serialized** and **actions_serialized** - Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers like product attributes, categories and attribute sets.

### Blocks

*File Name* - blocks.csv

This file is used to add or update blocks.  Updates are made by using the key of store_view_code and identifier.
Most information can be extracted from the Magento Admin UI from the Blocks grid by selecting the required blocks and selecting the *Export Content* Action

*Columns*

**store_view_code** - Optional. Comma delimited list of Store View Codes the block should be assigned to. If none is provided, the code of the view defined in settings.csv, or the global default of *default* is used.
> If you want a page to be available across all All Store Views, use the value of **admin** as the store_view_code

**identifier** - Required. Key used for updating

**is_active** - Optional Y/N. Defaults to Y

**title** - Required - Same as Block Title in the UI

**content** - Optional. Body of the block. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers for Page Builder compatibility

### Dynamic Blocks

*File Name* - dynamic_blocks.csv

This file is used to add or update Dynamic Blocks (Banners).  Updates are made by using the name as key. Setting of Related Promotions is not supported.

If pulling from existing configuration:
`select b.name as name, if(b.is_enabled=1,'Y','N') as is_enabled,'' as segments, 'admin' as store_view_code,b.types,bc.banner_content as banner_content
from magento_banner b, magento_banner_content bc 
where b.banner_id = bc.banner_id
and b.name in (...)`
You may to then update `store_view_code`,`segments` and `banner_content`

*Columns*

**name** - Required

**is_enabled** - Optional. Defaults to Y. Values Y/N

**type** - Optional. If left empty it will be set to Any Dynamic block Type. Other valid values are a comma delimited list of one or more of :`content`,`footer`,`header`,`leftcol`,`rightcol`

**store_view_code** - Optional. Default is `admin`, which makes it available across all stores. A comma delimited list of store view codes is supported

**segments** - Optional. Comma delimited lists of qualifying segment names. If left empty it will be made available to all segments

**banner_content** - Optional. Body of the block. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers for Page Builder compatibility


### Widgets

*File Name* - widgets.csv

This file is used to add or update widgets.  Updates are made by using the key of title

Because widgets have a complex data structure, it is recommended that they be created in an existing magento instance and exported via DB query:
`select wi.title, wi.instance_type,t.theme_path as 'theme', wi.store_ids as 'store_view_code', wi.widget_parameters,wi.sort_order,wp.page_group,wp.
layout_handle,wp.block_reference,wp.page_for,wp.entities,wp.page_template
from widget_instance_page wp, widget_instance wi, theme t
where wp.instance_id = wi.instance_id
and wi.theme_id = t.theme_id`

A single layout update per widget is supported at this time.

The result will need a few edits to work properly: The ids in `store_view_codes` will need to be replaced with their appropriate codes. Ids in `widget_parameters` will need to be replaced by tokens depending on the entity type as they will be substituted by the [**Content Substitution**](#content-substitution) process. The ids in `entities` will also need to be replaced by product or category tokens depending on the entity type you defined when you selected that the layout was only to be applied to specific categories

*Columns*
**title** - Required - Same as Widget Title in the UI

**store_view_code** - Optional.  Will default to `admin` (all stores). Can be a comma delimited list for applying to multiple views

**instance_type** - Required. Type as defined in the UI, which is the class name

**theme** - Required. Path of theme to apply the widget to (`Magento/blank`,`Magento/luma`)

**widget_parameters** - Optional. JSON structure of the definition of the widget. This will include layout information and content and options defined in Widget Options. Ids should be replaced with tokens for [**Content Substitution**](#content-substitution)

**sort_order** - Optional. Sort Order in UI

**page_group** - Optional. Display On in layout updates in UI

**layout_handle** - Optional. Layout applying to the page_group

**block_reference** - Optional. Container in UI, where the widget is positioned

**page_for** - Optional. (`all` or `specific`). Will be set as `specific` if the widget is applied to specific categories or products

**entities** - Optional.  Will be populated by ids if the widget is applied to specific products or categories. Ids should be replaced with tokens for [**Content Substitution**](#content-substitution)

**page_template** - Optional. System template used to apply the widget

### Pages

*File Name* - pages.csv

This file is used to add or update pages.  Updates are made by using the key of `store_view_code` and `identifier`
Most information can be extracted from the Magento Admin UI from the Pages grid by selecting the required blocks and selecting the *Export Content* Action

*Columns*
**store_view_code** - Optional. Store View the page should be assigned to. If none is provided, the code of the view defined in settings.csv, or the global default of *default* is used.
> If you want a page to be available across all All Store Views, use the value of **admin** as the store_view_code

**identifier** - Required. Url key of the page.

**title** - Required - Same as Page Title in the UI

**is\_active** - Optional (Y/N), defaults to Y

**page\_layout** - Optional. Default = cms-full-width. Value entered in Design->Layout section of UI.  Acceptable values include empty, 1column, 2columns-left, 2columns-right, 3columns, cms-full-width ,category-full-width, product-full-width

**meta\_keywords** - Optional

**meta\_title** - Optional

**meta\_description** - Optional

**content\_heading** - Optional. Content Heading in UI

**content** - Optional. Body of the page. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers for Page Builder compatibility

### Templates

*File Name* - templates.csv

This file is used to create Page Builder templates

*Columns*

**name** - Required. This is the key used for updates

**created_for** - Optional - Used to filter for selection UI. Values are page, block, dynamic_block, category, product.

**preview_image** - Optional. Name of the Thumbnail image of the template that is used in template selection UI. This should be saved from the existing template and stored in `media/.template-manager`. It will be something like `homepage5f6a43805b54f.jpg`

**content** - Optional (but then why bother). Body of the template. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers for Page Builder compatibility

### Reviews

*File Name* - reviews.csv

This file is used to add reviews and ratings to products. If a review already exists with the same summary for a product, it is skipped.

Quality, Price and Value rating codes are installed by default but the visibility is not set for a website, so those values could not be used unless activated in the admin before the data is imported.

> Out of Scope: Support for multiple ratings per review. Updating of existing reviews or ratings.

*Columns*

**store\_view\_code** - Optional. Will use the value defined in settings.csv, or use the installation default

**sku** - Required. Product to add the review to. If the product doesn't exist the row will be skipped

**rating\_code** - Required. Type of rating. This can be any value: e.g. Rating, Satisfaction, etc. Quality, Price and Value rating codes are installed by default but the visibility is not set for a website, so those values could not be used unless activated in the admin before the data is imported.

**rating\_value** - Required. Number of stars from 1 to 5


**summary** - Required. This is what a user would enter in the Summary field of the review form

**review** - Required. The text of the review

**reviewer** - Required. This is what a user would enter in the Nickname field of the review form

**email** - Optional. Email of a registered customer to attach review to.

### B2B Approval Rules

*File Name* - b2b_approval_rules.csv

This file is used to add and update B2B Approval Rules. `company_name` and `name` are keys and are used for updates. Currently the ability to use Approval Rules needs to be set manually per company in the Admin.

*Columns*

**company_name** - Required

**name** - Required

**description** - Optional

**is_active** - Optional (values Y/N), defaults to Y

**apply_to_roles** - Required. Comma delimited list of roles to apply the rule to. This includes any custom company roles created, along with `Default User`

**rule_type** - Required. Value is one of `grand_total`, `shipping_incl_tax`, `number_of_skus`

**rule** - Required. Value is one of `>`,`<`,`>=`,`<=`

**amount_value** - Required

**currency_code** - Optional, defaults to `USD`. Needs to be a valid currency code

**approval_from** - Required. Comma delimited list of approval roles. This includes any custom company roles created, along with `Default User`,`Company Administrator` and `Purchaser's Manager` 
### B2b Companies

*File Name* - b2b_companies.csv

This file is used to add companies. Updates are made by using `company_name` as key

*Columns*

**company_name** - Required

**site_code** - Optional. Will default to the `site_code` defined in `settings.csv`

**street** - Required

**city** - Required

**country_id** - Required

**region** - Required

**postcode** - Required

**telephone** - Required

**status** - Optional (Values Y/N), Defaults to Y

**credit_limit** - Optional

**company_email** - Optional, will default to `company_admin`

**company_admin** - Email Address of Company Admin as defined in `b2b_customers.csv`
### B2B Company Roles

*File Name* - b2b_company_roles.csv

This file is used to add company customer roles. Values cannot be updated, so each role will be recreated on update. It is recommended to create the roles in a Magento instance and then extract them from the DB
`select c.company_name,cr.role_name as 'role', cp.resource_id 
from company_permissions cp, company_roles cr, company c
where cp.role_id = cr.role_id
and cr.company_id = c.entity_id
and cr.role_name in ('Buyer') 
and cp.permission = 'allow'`

*Columns*

**company_name** - Required

**role** - Required. Name of role to create

**resource_id** - Required. Valid Magento ACL resource. Example - `Magento_Company::index`

### B2B Customers

*File Name* - b2b_customers.csv

This file is used to add the customers within a company. A regular customer file format can be used as outlined in [**customers.csv**](#customers)

There are a few required columns that will need to be added

**company_name** - As defined in `b2b_companies.csv`

**company_admin** - (Y/N) Sets this customer as the company admin. Only one admin per company is allowed

**role** - Customer role as defined in `b2b_company_roles.csv`

### B2B Requisition Lists

*File Name* - b2b_requisition_lists.csv

This file is used to add Requisition Lists to Customers. Updates are done by using `name` and `customer_email` as key.

*Columns*

**customer_email** - Required. Should be an email defined in `b2b_customers.csv`

**site_code** - Optional.  Will default to value in `settings.csv`. HOWEVER it needs to match the website that `customer_email` was created in

**name** - Required

**description** - Optional

**skus** - Optional. A comma and pipe delimited list of sku|quantity. *Example* - `24-MB01|5,10061|2 `. If no quantity is given it will be set to 1. If the sku is invalid the product will still be added, but will show as invalid in the requisition list

### B2B Sales Reps

*File Name* - b2b_sales_reps.csv

This file is used to add company sales reps. These are different in that they aren't company users, but have access to the Magento admin to process orders and quotes, build catalogs, etc. Update key is `username`.

*Columns*

**email** - Required

**username** - Required

**firstname** - Required

**lastname** - Required

**password** - Required

**company** - Required. Should match company in `b2b_companies.csv`

**role** - Required. Should match role in `admin_roles.csv`

### B2B Shared Catalogs

*File Name* - b2b_shared_catalogs.csv

This file is used to add and update Shared Catalogs. Update is done using `name` as key

*Columns*

**name** - Required

**companies** - Optional. Comma delimited list of companies defined in `b2b_companies.csv` to tie to the catalog

**type** - Optional. Values `Custom` or `Public`. Defaults to `Custom`. Remember you are only allowed one `Public` catalog

**description** - Optional

### B2B Shared Catalog Categories

*File Name* - b2b_shared_catalog_categories.csv

This file assigns the category and its contained products to a shared catalog. One row per category added.On an update the data is reset to the incoming values. 

*Columns*

**shared_catalog** - Required. Catalog as defined in `b2b_shared_catalogs`

**category** - Required. Full path of category to add *Example*:`B2B Root Category/Gear/Bags/Duffel`


### B2B Teams

*File Name* - b2b_teams.csv

This file is used to set up company structure, add teams and assign users to them. Only one level of nesting is supported. Teams are assigned to the admin user, and company customers assigned to the Team. On update, structure and members can be updated by `name`. However, `name` itself cannot be updated

*Columns*
**site_code** - Optional. Will default to value in `settings.csv`. This code needs to match the website that the `members` were created in

**name** - Required

**company_name** - Required. Company defined in `b2b_companies.csv`

**members** - Optional. Comma delimited list of email addresses defined in `b2b_customers.csv`

### B2B Negotiated Quotes

*File Name* - b2b_negotiated_quotes.json

This file is used to create negotiable quotes for the company. For now an existing quote cannot be updated. In case a quote is marked `ordered`, it will also create the associated order with it. 

*Columns*

**site_code** - Optional. Will default to value in `settings.csv`. This code needs to match the website that the `company users` were created in

**name** - Required. It is the Quote Name.

**email** - Required. It is the email of company users for which negotiable quote was created.

**status** - Required. Negotiable Quote status.

**creator_type_id** - Required. The type of user who created the quote.

**creator** - Required. The username or email of creator.


# Content substitution

There are some elements of content, particularly from Page Builder, that reference IDs of blocks, categories, etc. Because those IDs aren't known until something is installed, there needs to be a mechanism to reference those elements to be replaced with IDs later.

For example, the following code would be seen in Page Builder content when including a block

`{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="3" type_name="CMS Static Block"}}`

The block included (Contact us info) in the current installation has an id of 3, which may not be the case in any new data installation. In order to have the content work in other installations, we need to replace the ID of the block we want to include (3) with a string that includes its identifier (contact-us-info).  

`{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="{{block code="contact-us-info"}}" type_name="CMS Static Block"}}`

If no correct replacement is found, the substitution will not occur.

If you are using the GraphQL export, these substitutions will be made automatically

Here is a list of all substitutions currently supported

**Category Id** - `{{categoryid key="<url key of category>"}}`\
*example* - `{{categoryid key="shorts-men"}}`

**Category Url** - `{{categoryurl key="<url key of category>"}}`\
*example* - `{{categoryurl key="shorts-men"}}`

**Product Url** - `{{producturl sku="<sku>"}}`\
*example* - `{{producturl sku="24-MB01"}}`

**Product Id** - `{{productid sku="<sku>"}}`\
*example* - `{{productid sku="24-MB01"}}`

**Product Attribute** - `{{productattribute code="<product attribute code>:<attribute value>"}}`\
*example* - `{{productattribute code="activity:Running"}}`

**Product Attribute Set** - `{{attributeset name="<product attribute set name>"}}`\
*example* -  `{{attributeset name="Bag"}}`

**Customer Attribute** - `{{customerattribute code="<customer attribute code>:<attribute value>"}}`\
*example* - `{{customerattribute code="gender:Male"}}`

**Customer Group** - `{{customergroup name="<customer group name>"}}`\
*example* - `{{customergroup name="VIP"}}`

**Customer Segment** - `{{segment name="<segment name>"}}`\
*example* - `{{segment name="High Lifetime Value"}}`

**Block** - `{{block code="<block identifier>"}}`\
*example* - `{{block code="contact-us-info"}}`

**Dynamic Block** - `{{dynamicblock name="<block name>"}}`\
*example* - `{{dynamicblock name="VIP Header"}}`

**pages** - `{{pageid key="<page identifier>"}}`\
*example* - `{{pageid key="new-home-page"}}`

## Troubleshooting
The Data Installer should handle most problems by logging as much information as possible, while continuing on with other data types.  For example it may skip a file or specific row in a file if it encounters an issue.

Information about skipped rows or files will be displayed in the terminal when using a CLI import. Also standard importer information will be available for products, customers, advanced pricing and msi. This will include imported rows, invalid rows, etc.

For all methods, the same information is written in `var/logs/data_installer.log`. If it was a UI Upload or a GraphQL initiated import, the log information can be retreived via GraphQL query.

