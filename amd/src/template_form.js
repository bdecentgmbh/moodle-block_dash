define(['block_dash/codemirror',
    'block_dash/codemirror_mode_xml',
    'block_dash/codemirror_mode_handlebars',
    'block_dash/codemirror_mode_sql',
    'block_dash/codemirror_addon_matchbrackets',
], function(CodeMirror, xml2, handle) {
    CodeMirror.fromTextArea(document.getElementById("id_layout_mustache"), {
        lineNumbers: true,
        matchBrackets: true,
        mode: {name: 'handlebars', base: 'text/html'}
    });
    CodeMirror.fromTextArea(document.getElementById("id_query_template"), {
        mode: "text/x-mariadb",
        indentWithTabs: true,
        smartIndent: true,
        lineNumbers: true,
        matchBrackets : true,
        autofocus: true,
        extraKeys: {"Ctrl-Space": "autocomplete"},
        hintOptions: {tables: {
                users: ["name", "score", "birthDate"],
                countries: ["name", "population", "size"]
            }}
    });
});
