# Manage content of CMS Static blocks and Pages from files
The extension creates a file copy with the content of enabled static blocks and CMS pages. 
The copy is kept synchronized in both directions, meaning that saving a static block updates the file copy and vice versa! 
This allows working directly on the file instead of working in the textarea without autocompletion and other benefits of an IDE can provide.
In addition to this, it is possible to include the files in a workflow that purges CSS (which was the original scope of this approch).

File creation is disabled by default, you can enable one or more static blocks or pages in "System -> Configuration -> Content Management".

![config](https://github.com/magemega/openmage-mm_cmscontentfilemode/assets/5071467/d6fadb7c-f297-44f6-9545-4f57182f29f5)


The files are created in the following locations:
- app/design/{package}/{name}/template/cms/static_block/_static_block_identifier.html_
- app/design/{package}/{name}/template/cms/static_page/_url_key_page.html_

- app/design/rwd/default/template/cms/static_block/category_landingpage_vip.html
- app/design/rwd/default/template/cms/static_page/company.html

PS: The files are correctly renamed if you modify the identifier or url_key.

Support
-------
If you have any issues with this extension, open an issue on GitHub.

Contribution
------------
Any contributions are highly appreciated. The best way to contribute code is to open a
[pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)
