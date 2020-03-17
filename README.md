# Magento 2 Data Install Modules
**Purpose** :  To facilitate the loading of sample data by a series of generic .csv files
This file is optional.  If it is not included, the installation defaults will be used

## Sites, Stores, Views, Root Category
This file is used to add and update Stores, Store Views and Root Categories. The codes provided in the file are used to determine if a new element will be created or updated.
> Out of Scope: Updating of Codes and Root Category Name

*File Name* - stores.csv

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


## Product Attributes
This file is used to add and update Product Attributes and assign them to attribute sets. The codes provided in the file are used to determine if a new attribute will be created or updated.
Product attribute configurations can be complex. The purpose of this file is to address the most common settings.
> Out of Scope: Updating Attribute codes. Any attribute setting not currently listed

*File Name* - product_attributes.csv

*Columns*
frontend_label	frontend_input	is_required	option	default	attribute_code	position

**attribute\_code** - Always required. If the attribute\_code exists it will update the attribute with the provided information.  If it is a new code, it will create a new attribute. Attribute code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**default_label** - Required when creating a new attribute.

**frontend\_input** - Required when creating a new attribute. Catalog Input Type for Store Owner. Allowed values are xxxxxx

**is\_required** - Optional: Values = Y/N. Default = N

**options** - Required when input is Multi or Select. Carriage return delimited

**position** - Optional, Numeric.  Indicates the position of the attribute within the Attribute Group

**attribute\_set** - Carriage return delimited list of Attribute Sets that the Attribute will be added to.  Sets will be created as needed based on the Default set. If no value is given, the Attribute will be added to the Default set.

