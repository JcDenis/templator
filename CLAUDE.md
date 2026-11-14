# templator

Notes for AI assistants working on the Dotclear plugin templator.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Help you to manage additonnal tempaltes for your blog. You can copy existing templates, then edit them. You can attribute a template to a post or a category.

Admin page. Public side: widgets.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/Manage.php`: admin page of the module.
* `src/ManageVars.php`: admin page (vars).
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Pager.php`: backend pager class.
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/Templator.php`
* `src/Widgets.php`: widgets definition and rendering.
* `js/`: `admin.js`, `posts.js`.
* Widgets: `templatorWidget`.
* Behaviors used: `adminPostHeaders`, `adminPostFormItems`, `adminPageHeaders`, `adminPageFormItems`, `adminAfterPostCreate`, `adminBeforePostUpdate`, `adminAfterPageCreate`, `adminBeforePageUpdate`, `adminPostsActions`, `adminPagesActions`, `adminFiltersListsV2`, `initWidgets`, `urlHandlerBeforeGetData`.
* Requires tags, themeEditor (`requires` in `_define.php`).

## Pitfalls

* CI installs tags, themeEditor from the author's repositories, then from franck-paul's.

## Testing

* Check the admin page, the widgets (admin and public), the admin help, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
* The user guide `docs/fr/utilisation.md` describes the features: update it, and its screenshots, when the interface changes.
