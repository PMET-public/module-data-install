# Magento 2 Data Install Module

The Data Install module facilitates the loading of sample data by a series of generic .csv files. This allows for the easy creation/editing of data sets for particular scenerios. It also helps facilitate packaging of demos or verticals so they can be added to an existing site with demo data, or an empty site.

**As the Data Install supports B2B, at this time the B2B modules are required in Magento even if B2B features will not be used.**

## Installation Methods

### CLI (Preferred Method)

`bin/magento gxd:datainstall <module>`
Optional arguments:
`--fixtures[=FIXTURES]  Change fixtures directory [default: "fixtures"]`
 `--files[=FILES]        Comma delimited list of individual files to load`
 `-r, --reload[=RELOAD]      Force Reload`

Using the CLI has multiple advantages to the `setup:upgade` method

1. You don't need to use a Magento module unless you want to
1. The module becomes very simple with no need for the Setup classes
1. You can have a module included in the code or composer.json, but it will not add its data until you run the appropriate CLI command.  This allows you to have multiple data sets ready to go without having to load each one as they are needed.
1. The Magento `setup:upgrade` process was not built with the idea of installing large amounts of data across many data types and multiple stores. This can lead to errors especially when trying to install multiple data packs at the same time.

**If you are using the CLI method, your modules should not have the Setup classes.  This could lead to data conflicts and errors.**

###### Usage

- `bin/magento gxd:datainstall MySpace_MyData`
Install data from the `MySpace_MyData` module. This module can reside in either *vendor* or *app/code*

- `bin/magento gxd:datainstall var/import/importdata/MyData`
Install data from any directory under the Magento root.  In this case `var/import/importdata/MyData`. This does not need to be a Magento module, but only needs to contain the .csv files and media

- `bin/magento gxd:datainstall MySpace_MyData --fixtures=store1`
Use an alternate directory for the .csv files (default is *fixtures*). This would allow you to potentally have multiple data sets in the same module *fixtures*,*data*,*store1*, etc.

- `bin/magento gxd:datainstall MySpace_MyData --files=customers.csv,pages.csv`
Mostly used for testing.  You can pass a comma delimited list specific files you want loaded rather than loading everything

- `bin/magento gxd:datainstall MySpace_MyData -r` Each data pack is logged and will only install once. The `-r` option allows you to reinstall an existing data pack.  If you are going to reinstall, it is a good idea to clear the cache first.  Some configurations are retained in cache, and you may see errors around stores not being found or gxd namespace errors if you reinstall without clearing the cache.

- If you need to install multiple data packs at the same time, you can chain commands together:`bin/magento gxd:datainstall MySpace_MyData;bin/magento gxd:datainstall MySpace_MyData2;bin/magento gxd:datainstall MySpace_MyData3`

Sample Data Module - [https://github.com/PMET-public/module-storystore-sample](https://github.com/PMET-public/module-storystore-sample "https://github.com/PMET-public/module-storystore-sample")

### Setup:Upgrade (or install)

Although CLI is the preferred method, you can still create modules and install data during Magento installation or setup upgrade.  This method may not work when installing multiple data packs especailly around multiple sites stores and views. We have separated the process out to require 3 different setup classes in order to optomize for success.

Sample setup:upgrade compatible data pack

The installation is split into 3 Setup classes.

- `Setup/Patch/Data/InstallStores.php` - installs sites,stores & views
- `Setup/Patch/Data/Install.php` - config, categories, customers, segments, blocks, attributes
- `Setup/RecurringData.php` - remaning data points
Even though `RecurringData.php` is used, its first run is logged so it only runs once.

Sample Data Module - [https://github.com/PMET-public/module-storystore-sample](https://github.com/PMET-public/module-storystore-sample "https://github.com/PMET-public/module-storystore-sample")

------------

## Data Files

Each element of potential sample data is encapsulated in its own file:

[**settings.csv**](#Settings) - Optional file containing settings used by the install process.

[**stores.csv**](#Stores) - Used to create sites, stores, store views and root categories

[**Configuration files**](#Configuration) - **config_default.json, config_default.csv, config_vertical.json, config_vertical.csv, config_secret.json, config_secret.csv, config.json, config.csv**.  These files contain settings that would mostly be set in Stores->Configuration: Payment methods, store information, admin settings, etc.  See the [**Configuration files**](#Configuration) section for details on their usage.

[**admin\_roles.csv**](#admin-roles) - Creates customer groups

[**admin\_users.csv**](#admin-users) - Creates customer groups

[**customer\_groups.csv**](#customer-groups) - Creates customer groups

[**customer\_attributes.csv**](#customer-attributes) - Creates customer attributes

[**customer\_segments.csv**](#customer-segments) - Creates customer segments

[**customers.csv**](#customers)  - Creates customers. Also used to add customer data to autofill.

[**product_attributes.csv**](#product-attributes) - Creates product attributes and set

[**categories.csv**](#categories) - Creates categories

[**products.csv**](#products) - Creates simple and configurable products

**msi source and stock - tbd**

[**msi\_inventory.csv**](#msi-inventory) - Updates inventory for MSI sources

[**advanced\_pricing.csv**](#advanced-pricing) - Sets tier and group pricing

[**upsells.csv**](#upsells) - Used to create the Related Products rules

[**catalog\_rules.csv**](#catalog-rules) - Used to create the Catalog Promotion Rules

[**blocks**](#blocks) - Creates Blocks. Includes Page Builder compatibility

[**dynamic_blocks.csv**](#dynamic-blocks) - Creates Dynamic Blocks. Includes Page Builder compatibility

[**pages.csv**](#pages) - Creates and updates pages. Includes Page Builder compatibility

[**templates.csv**](#templates) - Create Page Builder templates from existing Page Builder content

[**reviews.csv**](#reviews) - Creates reviews and ratings

*To be added*
**widgets.csv**
**cart\_rules.csv**
**Staging**
**orders, refunds, credit memos**

Files are processed in the order as listed above.  This does potentially present a chicken/egg situation for some data points.  For example, Categories can contain Blocks that can contain Categories. There is a mechanism in place to defer adding sample data if the required elements arent yet installed. At this point it is mostly untested and not supported.

# Files

### Settings

*File Name* - settings.csv

Optional file. This file contains settings used by the install process. This file is optional if you are adding data to a base installation.  It will be used in a multi-store scenerio, or if you are going outside of some of the defaults. This will remove the requirement of having to use the included values in other data files.

*Columns* - **name,value**

*Recoginzed name/value pairs*

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

**is\_default\_site** - Optional: Allowed value =Y. There can only be one default site. If it is set muliple times, the last site updated will be the default. Default cannot be removed, it can only be assigned to a different site.

**store\_code** - Required when updating or adding a store or view. Store code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter. Code will be fixed automatically if needed

**store\_name** - Required when updating a store name, or creating a store

**store\_root\_category** - Optional: Default is the installation Default Category. Needs to be provided if a different Root Category is required. If the Root Category given does not exist, one will be created and assigned to the store.

**is\_default\_store** Optional: Allowed value = Y. There can only be one default store per site. If it is defined muliple times, the last store updated will be the default. The default store cannot be removed from a site, only changed to a different store.

**store\_view\_code** - Required when updating or adding a view. View code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**view\_name** - Required when updating a view name, or creating a view

**is\_default\_view** - Optional: Allowed value = Y. There can only be one default view per store. If it is defined muliple times, the last view updated will be the default. The default view cannot be removed from a store, only changed to a different view.

**view\_order** - Optional: Default is zero

**view\_is\_active** - Optional: values = Y/N. Default = N. If a view is set as default for a store, it cannot be deactivated.

**host** - Optional: Used to set the Base Urls for a site.  Should just be the domain name (example: luma.com).  This is set at the website level. If it needs to be set for another scope, that can be done in the config.json or config.csv files.

**theme** - Optional: Assigns a theme to the store view. This should be the path of the theme directory from the Vendor namespace. For example Magento/luma or MagentoEse/venia

### Configuration

*File Names* - config_default.json, config_default.csv, config_vertical.json, config_vertical.csv, config_secret.json, config_secret.csv, config.json, config.csv

The .json and .csv files are interchangable. Both formats serve the same purpose, so it is up to personal preference which format is used. All files are optional, but it is recommended to have the default file in order to create the settings to have a reasonablly operational store.

These files are used to set values that would normally be set in the store admin under Stores -> Configuration.

The purpose of having the multiple files (default, vertical,secret,config) is to allow the flexibility of having some settings that are likely to never change, or may be applicable to a specific vertical, and then override them with subsequent files.

##### File Processing Order

**config_default.json, config_default.csv** - These files would likely be induded in a base data pack, and would likely not be changed.  It would include common admin settings like security settings, basic payment and shippping settings, etc. These files would contain the basic settings to get a site up and running, and would likely not be changed.

**config_vertical.json, config_vertical.csv** - Adds to or overrides settings in the default files. These would normally be things that are applicable to the specific data set like Venia or Luma German, or Grocery. Generally should not be edited by the end user if they are loading a prepared data set

**config_secret.json, config_secret.csv** - This file could contain private information like API keys, passwords, etc.  That information is not required to go into this file, and could go in any configuration file. However it should be a best practice in order to easily remove private information if publically distributing the rest of the data pack

**config.json, config.csv** - These files are final files loaded and are used to add to or override any previous settings.  This is likely where an end user would make changes specific to their needs.

*json file format* - Needs to be documented. This is the same structure in the other json files, with nodes matching the path of the variable to set

*using the encode function* - needs to be documented

*CSV file Columns*  - the file format matches the values stored in the core\_config\_data table

**path** - Required. Path matching values set in the core\_config\_data table e.g. `general/locale/code`
**value** Required. Value to set
**scope** - Optional. Allowed scopes are `websites`, `stores`, `default`. Defaults to `default`.
**scope\_code** - Required if scope is `websites` or `stores`. Include the scope_code of the site or store you want the value set for

### Admin Roles

*File Name* - admin\_roles.csv

Optional file: Creates roles and their settings for admin users

*Columns*
**name** - Required. Name of the role
**resource_id** - Resource to activate for the role (eg: `Magento_Backend::admin`) one per row

Resource Ids can be obtained by createing a role in the UI and retrieving the information from the authroization_rule table.

### Admin Users

*File Name* - admin\_users.csv

Optional file: Creates users for the Magento Admin

*Columns*
**email** - Required.
**username** - Required.
**firstname** - Required.
**lastname** - Required.
**role** - Optional. Needs to be an existing role

### Customer Groups

*File Name* - customer\_groups.csv

Used to create customer groups
> Out of Scope: Renaming cusomter groups. Assigning Tax Class.

*Column*
**name** - Required. Name of the customer group

### Customer Attributes

*File Name* - customer\_attributes.csv

This file is used to add and update customer attributes
Customer attribute configurations can be complex. The purpose of this file is to address the most common settings.
> Out of Scope: Updating Attribute codes. Any attribute setting not currently listed

*Columns*

**attribute\_code** - Always required. If the attribute\_code exists it will update the attribute with the provided information.  If it is a new code, it will create a new attribute. Attribute code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**frontend\_label** - Required when creating a new attribute.

**frontend\_input** - Required when creating a new attribute. Catalog Input Type for Store Owner. Allowed values are text, textarea, texteditor, date, boolean, multiselect, price

**is\_required** - Optional: Values = Y/N. Default = N

**options** - Required when input is Multi or Select. Values to show, carriage return delimited

**position** - Optional, Numeric.  Indicates the position of the attribute within the Attribute Group

### Customer Segments

*File Name* - customer\_segments.csv

This file is used to add and update customer segments.
Because segments are complex, the method currently in use is to create a segment in a test enviornment and then export that data out of the database to put in the .csv file

*Columns*

**name** - Always required. Name of the segment shown in the UI and also the key used for updates.

**site\_code** - Optional. Single site_code or comma delimited list for multiple sites. Will take the value from settings.csv if not provided.

**description** - Optional. Description of the segment

**is\_active** - Optional: Values = Y/N. Default = Y

**apply\_to** - Optional. Defaults to 0.
Accepted values 2= Apply to Visitors, 1= Apply to Registered Users, 0= Both Visitors and Registered

**conditions_serialized** - Optional (but if you dont put anything in, then really whats the point?) - This is taken from the database magento_customersegment_segment.conditions_serialized column. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers like product and customer attributes.  ID values for Region and Country should remain in place as they should be consistant across installations.

### Customers

*File Name* - customers.csv

Optional file: Used to create customers

Uses the same file format as the native Magento customer import with the exeption of one column:
**add_to_autofill** - Optional.  This will add the customer as a selectable option to the [Autofil Module](https://github.com/PMET-public/module-autofill "Autofil Module")

At this time, only one address is supported and used for both billing and shipping.  However, new addresses can be added from a second module, esentially updating the customer but adding, not replacing addresses.  Last address in getst set as default.

### Categories

*File Name* - categories.csv

This file is used to create categories. Categories are also created during product import so this file may be optional. It can be used if you want control over position and visibility.

> Out of Scope: Updating existing categories. Setting categories for specific views. Support for Layout, landing page, image and display mode attributes is coming.

*Columns*

**name** - Required. This is what will show on the storefront

**url\_key** - Required. Needs to be unique

**path** - If left empty, the category will be top level. Subcategories will need to have their parents listed. For example

- Women (no path)
  - - - Tops (Path = Women)
          - - - Sweaters (Path = Women/Tops)
          - - - Jackets (Path = Women/Tops)

Parent categories need to be in the file before the child categories

**active** - Optional: Values = 1/0. Default = 1

**is\_anchor** - Optional: Values = 1/0. Default = 1

**include\_in\_menu** - Optional: Values = 1/0. Default = 1

**position** - Optional, Numeric.  Indicates the position of the category within its specific branch

**description** - Optional.  Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers for Page Builder compatibility

**display_mode** - Optional. Default=PRODUCTS. Allowed values: PRODUCTS, PAGE, PRODUCTS_AND_PAGE

### Products

*File Name* - products.csv

The standard Magento Product import file is used. If you export from an existing store, you may need to make the following adjustments:

- Change any store codes, website, attribute set or category definitions to match your new configuration
- Image references will be the path of the final image *example:* `i/m/image.jpg`. Those will need to be updated to the path of your import source.  Most likely the path will be removed and just the file name (`image.jpg`) will be used

### MSI Inventory

*File Name* - msi_inventory.csv

The standard Magento Stock Sources import file is used.

**source\_code** - MSI Source

**sku** - Procuct sku

**qty** - Inventory to assign to source

**status** - Values (1/0) - sets if the product will be listed as In Stock for the source

### Advanced Pricing

*File Name* - advanced\_pricing.csv

The standard Magento Advanced Pricing import file is used. If you export from an existing store, you may need to make adjustments in websites or groups

### Product Attributes

*File Name* - product\_attributes.csv

This file is used to add and update Product Attributes and assign them to attribute sets. The codes provided in the file are used to determine if a new attribute will be created or updated.
Product attribute configurations can be complex. The purpose of this file is to address the most common settings. All settings are supported using database column names and values.


> Out of Scope: Swatches

*Columns*

**attribute\_code** - Always required. If the attribute\_code exists it will update the attribute with the provided information.  If it is a new code, it will create a new attribute. Attribute code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**frontend\_label** - Required when creating a new attribute.

**frontend\_input** - Required when creating a new attribute. Catalog Input Type for Store Owner. Allowed values are text, textarea, texteditor, date, boolean, multiselect, select, price

**is\_required** - Optional: Values = Y/N. Default = N

**option** - Required when input is Multi or Select. Values to show, carriage return delimited

**position** - Optional, Numeric.  Indicates the position of the attribute within the Attribute Group

**attribute\_set** - Optional. Carriage return delimited list of Attribute Sets that the Attribute will be added to.  Sets will be created as needed based on the Default set. If no value is given, the Attribute will be added to the Default set.

**only\_update\_sets** - Optional Value=Y. Only requires attribute\_code. This would be flagged in the case where the only action is to add an attribute to a set.  Most likely usage would be for assigning default system attributes to a set.

*Translating Front End Labels*
After the attributes are created, the translation of front end lables for additional stores can be added with a simpler file. Only one store code per front end lable is allowed per file at this point

**store\_view\_code** - Optional, will set the default label if not defined

**attribute\_code** - Required

**frontend\_label** - Required

### Upsells

*File Name* - upsells.csv

This file is used to add Related Products rules. At this time, the easiest method is to create the rules in an existing store, then take the serialized values from the database

*Columns*

**name** - Required. Used as key to update existing rules

**is\_active** - Y or N, Default = Y

**conditions\_serialized** - Value taken from the`conditions_serialized` column in the `targetrule` table. Content is run through the [**Content Substitution**](#content-substitution) process that will replace identifiers, most likely category ids. Make sure to double quotes for csv file compatibilit.

**actions\_serialized** - Value taken from the`actions_serialized` column in the `targetrule` table. Content is run through the [**Content Substitution**](#content-substitution) process that will replace identifiers, most likely category ids. Make sure to double quotes for csv file compatibility.

**positions\_limit** - Numeric. Max number of products to display

**apply to** - Values: related, upsells, crosssells

**sort_order** - Numeric

### Catalog Rules

*File Name* - catalog\_rules.csv

This file is used to add and update catalog promotion rules.
Because rule definitions are complex, the method currently in use is to create a catalog rule in a test enviornment and then export that data out of the database to put in the .csv file

*Columns*

**name** - Always required. Name of the rule shown in the UI and also the key used for updates.

**site\_code** - Optional. Single site_code or comma delimited list for multiple sites. Will take the value from settings.csv if not provided.

**description** - Optional. Description of the rule

**is\_active** - Optional: Values = Y/N. Default = Y

**conditions_serialized** - Optional (but if you dont put anything in, then really whats the point?) - This is taken from the database catalogrulet.conditions_serialized column. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers like product attributes.

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

### Blocks

*File Name* - blocks.csv

This file is used to add or update blocks.  Updates are made by using the key of store_view_code and identifier

*Columns*
**store_view_code** - Optional. Store View the page should be assigned to. If none is provided, the code of the view defined in settings.csv, or the global default of *default* is used.
> If you want a page to be available across all All Store Views, use the value of **admin** as the store_view_code

**identifier** - Required.

**title** - Required - Same as Block Title in the UI

**content** - Optional. Body of the page. Content will be run through the [**Content Substitution**](#content-substitution) process that will replace identifiers for Page Builder compatibility

### Pages

*File Name* - pages.csv

This file is used to add or update pages.  Updates are made by using the key of store_view_code and identifier

> Out of Scope: Any property of a page that is not listed.

*Columns*
**store_view_code** - Optional. Store View the page should be assigned to. If none is provided, the code of the view defined in settings.csv, or the global default of *default* is used.
> If you want a page to be available across all All Store Views, use the value of **admin** as the store_view_code

**identifier** - Required. Url key of the page.

**title** - Required - Same as Page Title in the UI

**is\_active** - Optional (Y/N), defaults to Y

**page\_layout** - Optional. Default = cms-full-width. Value entered in Design->Layout section of UI.  Acceptable values include empty, 1column, 2columns-left, 2columns-right, 3columns, cms-full-width ,category-full-width, product-full-width

**meta\_keywords** - Optional

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

> Out of Scope: Support for mulitple ratings per review. Updating of existing reviews or ratings.

*Columns*

**store\_view\_code** - Optional. Will use the value defined in settings.csv, or use the installation default

**sku** - Required. Product to add the review to. If the product doesn't exist the row will be skipped

**rating\_code** - Required. Type of rating. This can be any value: e.g. Rating, Satisfaction, etc. Quality, Price and Value rating codes are installed by default but the visibility is not set for a website, so those values could not be used unless activated in the admin before the data is imported.

**summary** - Required. This is what a user would enter in the Summary field of the review form

**review** - Required. The text of the review

**reviewer** - Required. This is what a user would enter in the Nickname field of the review form

**email** - Optional. Email of a registered customer to attach review to.

Content
Note on pages...names to use to replace the default install pages

# Content subsitution

There are some elements of content, particularily from Page Builder, that reference IDs of blocks, categories, etc. Because those IDs aren't known until something is installed, there needs to be a mechanism to reference those elements to be replaced with IDs later.

For example, the following code would be seen in Page Builder content when including a block

`{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="3" type_name="CMS Static Block"}}`

The block included (Contact us info) in the current installation has an id of 3, which may not be the case in any new data installation. In order to have the content work in other installations, we need to replace the ID of the block we want to include (3) with a string that includes its idendifier (contact-us-info).  

`{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="{{block code="contact-us-info"}}" type_name="CMS Static Block"}}`

If no correct replacement is found, the substituion will not occur.

Here is a list of all substitutions currently supported

**Category Id** - `{{categoryid key="<url key of category>"}}`\
*example* - `{{categoryid key="shorts-men"}}`

**Category Url** - `{{categoryurl key="<url key of category>"}}`\
*example* - `{{categoryurl key="shorts-men"}}`

**Product Url** - `{{producturl sku="<sku>"}}`\
*example* - `{{producturl sku="24-MB01"}}`

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

# Content export
