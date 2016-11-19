<?php

$baseDir = dirname(__FILE__);
$finder = \Symfony\CS\Finder::create()
    // ->exclude('data')
    // ->exclude('storage')
    ->exclude('vendor')
    ->exclude('.svn')
    ->exclude('.git')
    ->notPath('data')
    ->notPath('storage')
    ->notPath('server')
    ->notPath('src/vendor')
    ->notPath('addons/theme')
    ->in($baseDir)
;

return \Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(array(
        'double_arrow_multiline_whitespaces',
        'duplicate_semicolon',
        'blankline_after_open_tag',
        'concat_without_spaces',
        'function_typehint_space',
        'join_function',
        'list_commas',
        'multiline_array_trailing_comma',
        'namespace_no_leading_whitespace',
        'no_blank_lines_after_class_opening',
        'object_operator',
        'phpdoc_indent',
        'phpdoc_no_access',
        'phpdoc_no_empty_return',
        'phpdoc_params',
        'phpdoc_scalar',
        'phpdoc_to_comment',
        'remove_leading_slash_use',
        'remove_lines_between_uses',
        'return',
        'single_blank_line_before_namespace',
        'spaces_after_semicolon',
        'spaces_cast',
        'standardize_not_equal',
        'unneeded_control_parentheses',
        'unused_use',
        'whitespacy_lines',
        'single_quote',
        'include',
        'unalign_double_arrow',
        'unalign_equals',
        'ternary_spaces',
        'operators_spaces',
        'trim_array_spaces',
        'array_element_white_space_after_comma',
        'array_element_no_space_before_comma',
    ))
    ->finder($finder)
;
