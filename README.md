# Magento 2 Data Install Module
The Data Install module facilitates the loading of sample data by a series of generic .csv files. This allows for the easy creation/editing of data sets for particular scenerios. It also helps facilitate packaging of demos or verticals so they can be added to an existing site with demo data, or an empty site.

Each element of potential sample data is encapsulated in its own file:

**settings.csv** - This file contains settings used by the install process. This file is optional if you are adding data to a base installation.  It will be used in a multi-store scenerio, or if you are going outside of some of the defaults

[**stores.csv**](#stores.csv) - Used to create sites, stores, store views and root categories

**config_default.json** - Generally not edited.  This contains settings that are common across all demos, and would normaly be set in Stores->Configuration: Payment methods, basics store information, admin settings, etc.

**config_vertical.json** - Adds to or overrides settings from config_default.json. These would normally be things that are applicable to the specific data set like Venia or Luma German

**config.json & config.csv** - Adds to or overrides settings from the default and vertical files. These can be used to add more specific customizations. It can be done in the .json format or in .csv

**customer_groups.csv** - Creates customer groups

**customer_attributes.csv** - Creates customer attributes

**customers.csv** - Creates customers. Also used to add customer data to autofill.

[**product_attributes.csv**](#product_attributes.csv) - Creates product attributes and set

**categories.csv** - Creates categories

**products.csv** - Creates simple and configurable products

**blocks.csv** - Creates Blocks. Includes Page Builder compatibility

**dynamic_blocks.csv** - Creates Dynamic Blocks. Includes Page Builder compatibility

**pages.csv** - Creates pages. Includes Page Builder compatibility

*To be added*
**widgets.csv**
**downloadable_products.csv**
**bundled_products.csv**
**grouped_products.csv**
**virtual_products.csv**
**customer_segments.csv**
**cart_rules.csv**
**catalog_rules.csv**
**Staging**
**MSI**
**orders, refunds, credit memos**

Files are processed in the order as listed above.  This does potentially present a chicken/egg situation for some data points.  For example, Categories can contain Blocks that can contain Categories. There is a mechanism in place to defer adding sample data if the required elements arent yet installed. At this point it is mostly untested and not supported.


# Files

### stores.csv
This file is used to add and update Stores, Store Views and Root Categories. The codes provided in the file are used to determine if a new element will be created or updated.
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

**view\_code** - Required when updating or adding a view. View code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**view\_name** - Required when updating a view name, or creating a view

**is\_default\_view** - Optional: Allowed value = Y. There can only be one default view per store. If it is defined muliple times, the last view updated will be the default. The default view cannot be removed from a store, only changed to a different view.

**view\_order** - Optional: Default is zero

**view\_is\_active** - Optional: values = Y/N. Default = N. If a view is set as default for a store, it cannot be deactivated.

**host** - Optional: Used to set the Base Urls for a site.  Should just be the domain name (example: luma.com)


### product_attributes.csv
This file is used to add and update Product Attributes and assign them to attribute sets. The codes provided in the file are used to determine if a new attribute will be created or updated.
Product attribute configurations can be complex. The purpose of this file is to address the most common settings.
> Out of Scope: Updating Attribute codes. Any attribute setting not currently listed

*File Name* - product_attributes.csv

*Columns*

**attribute\_code** - Always required. If the attribute\_code exists it will update the attribute with the provided information.  If it is a new code, it will create a new attribute. Attribute code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**frontend_label** - Required when creating a new attribute.

**frontend\_input** - Required when creating a new attribute. Catalog Input Type for Store Owner. Allowed values are xxxxxx

**is\_required** - Optional: Values = Y/N. Default = N

**options** - Required when input is Multi or Select. Carriage return delimited

**position** - Optional, Numeric.  Indicates the position of the attribute within the Attribute Group

**attribute\_set** - Carriage return delimited list of Attribute Sets that the Attribute will be added to.  Sets will be created as needed based on the Default set. If no value is given, the Attribute will be added to the Default set.

Content
Note on pages...names to use to replace the default install pages

## Content export
## Content subsitution
## Creating your own data import module
