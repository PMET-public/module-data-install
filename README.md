# Magento 2 Data Install Module
The Data Install module facilitates the loading of sample data by a series of generic .csv files. This allows for the easy creation/editing of data sets for particular scenerios. It also helps facilitate packaging of demos or verticals so they can be added to an existing site with demo data, or an empty site.

Each element of potential sample data is encapsulated in its own file:

[**settings.csv**](#Settings) - Optional file containing settings used by the install process.

[**stores.csv**](#Stores) - Used to create sites, stores, store views and root categories

**config_default.json** - Should not be edited.  This contains settings that are common across all demos, and would normaly be set in Stores->Configuration: Payment methods, basics store information, admin settings, etc.

**config_vertical.json** - Adds to or overrides settings from config_default.json. These would normally be things that are applicable to the specific data set like Venia or Luma German. Generally should not be edited unless creating a re-usable data set

[**config.json & config.csv**](#Config) - Adds to or overrides settings from the default and vertical files. These can be used to add more specific customizations. It can be done in the .json format or in .csv

[**customer\_groups.csv**](#customer-groups) - Creates customer groups

[**customer\_attributes.csv**](#customer-attributes) - Creates customer attributes

**customers.csv** - Creates customers. Also used to add customer data to autofill.

[**product_attributes.csv**](#product-attributes) - Creates product attributes and set

**categories.csv** - Creates categories

**products.csv** - Creates simple and configurable products

**blocks.csv** - Creates Blocks. Includes Page Builder compatibility

**dynamic_blocks.csv** - Creates Dynamic Blocks. Includes Page Builder compatibility

**pages.csv** - Creates pages. Includes Page Builder compatibility

[**reviews.csv**](#reviews) - Creates reviews and ratings

*To be added*
**widgets.csv**
**downloadable_products.csv**
**bundled\_products.csv**
**grouped\_products.csv**
**virtual\_products.csv**
**customer\_segments.csv**
**cart\_rules.csv**
**catalog\_rules.csv**
**Staging**
**MSI**
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

**host** - Optional: Used to set the Base Urls for a site.  Should just be the domain name (example: luma.com)

**theme** - Optional: Assigns a theme to the store view. This should be the path of the theme directory from the Vendor namespace. For example Magento/luma or MagentoEse/venia


### Config
*File Name* - config.json & config.csv

Optional file. These files are used to set values that would normally be set in the store admin under Stores -> Configuration. They will add to or override settings defined in config_default.json and config_vertical.json

*json file format* - This is the same structure in the other json files, with nodes matching the path of the variable to set

*config.csv* - This file will be the most likely one you will edit

*Columns* 

**path** - Required. Path matching values set in the core\_config\_data table e.g. `general/locale/code`
**value** Required. Value to set
**scope** - Optional. Allowed scopes are websites, stores, default. Defaults to default.
**scope\_code** - Required if scope is websites or stores. Include the scope_code of the site or store you want the value set for

### Customer Groups
*File Name* - customer\_groups.csv

Optional file: Used to create customer groups
> Out of Scope: Updating existing customer groups


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



### Product Attributes
*File Name* - product\_attributes.csv

This file is used to add and update Product Attributes and assign them to attribute sets. The codes provided in the file are used to determine if a new attribute will be created or updated.
Product attribute configurations can be complex. The purpose of this file is to address the most common settings.
> Out of Scope: Updating Attribute codes. Any attribute setting not currently listed



*Columns*

**attribute\_code** - Always required. If the attribute\_code exists it will update the attribute with the provided information.  If it is a new code, it will create a new attribute. Attribute code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**frontend\_label** - Required when creating a new attribute.

**frontend\_input** - Required when creating a new attribute. Catalog Input Type for Store Owner. Allowed values are text, textarea, texteditor, date, boolean, multiselect, price

**is\_required** - Optional: Values = Y/N. Default = N

**options** - Required when input is Multi or Select. Values to show, carriage return delimited

**position** - Optional, Numeric.  Indicates the position of the attribute within the Attribute Group

**attribute\_set** - Carriage return delimited list of Attribute Sets that the Attribute will be added to.  Sets will be created as needed based on the Default set. If no value is given, the Attribute will be added to the Default set.

### Reviews
*File Name* - reviews.csv

This file is used to add reviews and ratings to products. If a review already exists with the same summary for a product, it is skipped.

Quality, Price and Value rating codes are installed by default but the visibility is not set for a website, so those values could not be used unless activated in the admin before the data is imported.


> Out of Scope: Support for mulitple ratings per review. Updating of existing reviews or ratings.


sku,rating_code,rating_value,summary,review,reviewer,email
*Columns*

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
*example* - `{{categoryid key="shorts-men"}`

**Category Url** - `{{categoryurl key="<url key of category>"}}`\
*example* - `{{categoryurl key="shorts-men"}`

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

# Creating your own data import module
