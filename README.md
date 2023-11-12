# Manage content of CMS Static blocks and Pages from files
> ## 🔥 Auto compile Tailwindcss [BETA]
> If you're a fan of Tailwind CSS, you must definitely try the BETA feature that auto-compiles the files and generates a CSS with Tailwind classes!
> You can also create your own reusable templates and add them to TinyMCE!

---

## Requirements
- [OpenMage](https://github.com/OpenMage/magento-lts) [^20.2.0] : starting from this version, tinyMCE 6 has been integrated

> NOTE: With some modifications, it would also work with the old editor. If you're interested, open a pull request, but the advice is to update.

## Description

The extension creates a file copy and keeps it synced with the content of enabled static blocks and CMS pages.

The copy is kept synchronized in both directions, meaning that saving a static block updates the file copy and vice versa! 

This allows working directly on the file with all the benefits that an IDE can offer, such as auto-completion, syntax highlighting, and more, instead of working in the textarea.

It is very useful when there is a lot of HTML markup in a CMS content, for example on the homepage or if you have to deal with themes that heavily rely on static blocks like Porto (which I particularly dislike because of this).

In addition to this, it's possible to include the created files in a workflow for CSS purging for example (and this is the original purpose for which it was created).

File creation is disabled by default, you can enable one or more static blocks or pages in "System -> Configuration -> Content Management".

![config](https://github.com/magemega/openmage-mm_cmscontentfilemode/assets/5071467/503d5d3e-46eb-4868-8fff-815e996bbeab)



The files are created in the following locations:
- `app/design/{package}/{name}/template/cms/static_block/`_static_block_identifier.html_
- `app/design/{package}/{name}/template/cms/static_page/`_url_key_page.html_

For example:
- `app/design/rwd/default/template/cms/static_block/category_landingpage_vip.html`
- `app/design/rwd/default/template/cms/static_page/company.html`

### Notes:
- files are created for each package/name corresponding to every enabled store view for the CMS
- files are correctly renamed if you modify the identifier or url_key
- ! files are not deleted if a static block/page is removed 

## 🔥 TailwindCSS Features [BETA]

Enable TailwindCSS compiler in configuration and unlock the power of [TailwindCSS](https://tailwindcss.com/docs/utility-first)!

1. **Compile TailwindCSS classes and preview in Tinymce Editor**
    
    Utilize TailwindCSS classes in your content and automatically compile them into a CSS file that will be added to your frontend in this location:

    - `https://openmage.dev/skin/frontend/rwd/default/css/tailwind.css`

2. **Create templates of reusable blocks**
    
    You can create reusable templates inside your theme in this path:
    
    - `app/design/{package}/{name}/template/cms/tinymce_templates/`

    Example:
    
    `app/design/rwd/default/template/cms/tinymce_templates/grid-4-columns.html`
    ```html
        <div class="grid grid-cols-4 gap-4 font-mono text-white text-sm text-center font-bold leading-6 bg-stripes-fuchsia rounded-lg mt-12 mb-12">
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">01</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">02</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">03</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">04</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">05</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">06</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">07</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">08</div>
            <div class="p-4 rounded-lg shadow-lg bg-fuchsia-500">09</div>
        </div>
    ```
    
    Then you can select the templates in tinyMCE and add to your content.
    ![tinyMCETemplateBtn](https://github.com/magemega/openmage-mm_cmscontentfilemode/assets/5071467/93918c71-0587-4671-b753-6e766141b6ff)
    ![tinyMCETemplatePopup](https://github.com/magemega/openmage-mm_cmscontentfilemode/assets/5071467/457868c7-1660-42be-b951-0b6b35b916a8)



4. **Customize TailwindCSS compiler with your own tailwind.config.js and base file**

    You can create your own tailwind.config.js inside your theme skin css directory

    for example to enable preflight (disabled by default), add prefix like 'tw-' or add plugins.

    **Example:**

    Customize TailwindCSS config:
    `app/design/frontend/rwd/default/css/tailwind.config.js`
    ```js
    /** @type {import('tailwindcss').Config} */
    module.exports = {
        prefix: '',
        corePlugins: {
            preflight: true,
        },
        theme: {
            extend: {},
        },
        plugins: [
            require('@tailwindcss/forms'),
            require('@tailwindcss/typography'),
        ],
    }
    ```
    You can find default config in lib/tailwindcss/tailwind.config.js, no base CSS is added by default.

    > PS: I recommend you to add a prefix 'tw-' to Tailwind classes and keep [preflight ](https://tailwindcss.com/docs/preflight) disabled if your theme is not build with TailwindCSS (for example, the rwd/default theme includes the `.block` class, which corresponds to `display: block;` in tailwind).


    You can also create your TailwindCSS entry point to add/remove components or extend layers

    `app/design/frontend/rwd/default/css/tailwind-base.css`
    ```css
    @tailwind base;
    @tailwind components;
    @tailwind utilities;

    @layer components {
        .my-button-primary {
            @apply bg-blue-500 text-white font-bold py-2 px-4 rounded;
        }
    }
    ```
    Then you can use in your content like:
    ```html
    <button class="my-button-primary">SEND</button>
    ```

Screenshot
----------
![demotailwindopenmage_template](https://github.com/OpenMage/magento-lts/assets/5071467/21998211-872f-4809-89a6-f71e093f5faa)
![demo1](https://github.com/magemega/openmage-mm_cmscontentfilemode/assets/5071467/6b23373f-db11-482a-b5c3-6c188b828324)
![demo2](https://github.com/magemega/openmage-mm_cmscontentfilemode/assets/5071467/dd2a3817-f183-4f24-a9cb-4640bdfefd9c)



## ⚠️ Highly experimental

You can configure TailwindCSS to look into all your theme files and forcibly recompile TailwindCSS while development (enabled only for Developer IPs).

Example:
```js
/** @type {import('tailwindcss').Config} */
module.exports = {
    prefix: '',
    corePlugins: {
        preflight: false,
    },
    content: [
        '../../../app/design/frontend/base/default/**/*.phtml',
        '../../../app/design/frontend/rwd/default/**/*.phtml',
        '../../../app/design/frontend/rwd/lightclean/**/*.phtml',
        '../../../skin/frontend/rwd/lgihtclean/js/*.js',
        '../../../js/*.js'
    ],
    theme: {
        extend: {},
    },
    plugins: [
        //require('@tailwindcss/forms'),
        //require('@tailwindcss/typography'),
    ],
}
```
> NOTE: It has never been tested, so I don't know what the performance of the binary compiler is with so many files.

> You can experiment starting from another interesting project [openmage-theme-lanzarote](https://github.com/fballiano/openmage-theme-lanzarote) without the need to install BUN, but I don't know how it performs. Let me know!



### Support
-------
If you have any issues with this extension, please open an issue on GitHub.

### Contribution
------------
Any contributions are highly appreciated. The best way to contribute code is to open a
[pull request on GitHub](https://help.github.com/articles/using-pull-requests).

### Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)
