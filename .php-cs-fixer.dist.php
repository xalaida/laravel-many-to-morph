<?php

return (new PhpCsFixer\Config())
	->setFinder(
		PhpCsFixer\Finder::create()->in(__DIR__)
	)
	->setRules([
		'@PSR12' => true,
		'global_namespace_import' => [
			'import_classes' => true,
			'import_constants' => true,
			'import_functions' => true,
		],
		'no_unused_imports' => true,
		'not_operator_with_successor_space' => true,
		'php_unit_test_annotation' => [
			'style' => 'annotation',
		],
	])
	->setIndent("\t")
	->setRiskyAllowed(true)
	->setCacheFile(
		is_dir(__DIR__.'/.cache')
			? __DIR__.'/.cache/.php-cs-fixer.cache'
			: __DIR__.'/.php-cs-fixer.cache'
	);
