# Coda Format Source

### Coda Plug-in to format HTML source code

Select a block of code or the entire document, and format mixed HTML/PHP using the Tidy library, with some post-processing cleanup.

More specifically, the plug-in will:
- Preserve, unaltered, the contents of anything before opening `<body>` tag (e.g. `<head>` and initial PHP blocks)
- Preserve `<body>` attributes (i.e. `class` or `id`)
- Tidy `<body>` content (or selection) using pre-defined Tidy configuration
- Add line break with indentation before `<iframe>`, `<input>`, `<script>` tags, and HTML comments
- Add line break with indentation before PHP blocks, unless they contain an echo statement
- Remove line breaks inside `<script>` tags containing a `src` attribute
- Remove whitespace inside`textarea`opening and closing tags
- Convert four spaces to tab

**Important!** Plug-in is configured to use MacPorts versions of PHP and Tidy `#!/opt/local/bin/php`, because the version of Tidy that ships with OS X (Yosemite) is too old to support the`drop-empty-elements` rule. This rule needs to be set to false, or Tidy will completely remove empty tags (for instance, `<i class="fa fa-star"></i>`). Once you have installed MacPorts (and preferrably, Xcode), you should be able to run:
- `sudo port install php55`
- `sudo port install tidy`
- `sudo port install php55-tidy`
(Replace with your preferred version of PHP)

