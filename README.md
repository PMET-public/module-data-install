# Magento 2 Data Install Modules
**Purpose** :  To facilitate the loading of sample data by a series of generic .csv files


## Sites, Stores, Views, Root Category
This file is used to add and update Stores, Store Views and Root Categories. The codes provided in the file are used do determine if a new element will be created or updated.

*File Name* - stores.csv

*Columns*

**site\_code** - Always required. If the site\_code exists it will update the site with the provided information.  If it is a new site code, it will create a new site. Website code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**site\_name** - Required when updating a site name, or creating a new site

**site\_order** - Optional, default is zero

**is\_default\_site** - Optional, value =Y. There can only be one default site. If it is set muliple times, the last site updated will be the default. Default cannot be removed, it can only be assigned to a different site.

**store\_code **- Required when updating or adding a store or view. Store code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter. Code will be fixed automatically if needed

**store\_name** - Required when updating a store name, or creating a store

**store\_root\_category** - Required when creating a new store or changing a Root Category. If the Root Category given does not exist, one will be created and assigned to the store.

**is\_default\_store** Optional: value = Y. There can only be one default store per site. If it is defined muliple times, the last store updated will be the default. The default store cannot be removed from a site, only changed to a different store.

**view\_code** - Required when updating or adding a view. View code may only contain letters (a-z), numbers (0-9) or underscore (\_), and the first character must be a letter.  Code will be fixed automatically if needed

**view\_name** - Required when updating a view name, or creating a view

**is\_default\_view** - Optional: value = Y. There can only be one default view per store. If it is defined muliple times, the last view updated will be the default. The default view cannot be removed from a store, only changed to a different view.

**view\_order** - Optional Default is zero

**view\_is\_active** - Optional: values = Y/N. Default = N. If a view is set as default for a store, it cannot be deactivated.
