<?php

/* Copyright (c) 2016 Richard Klees, Extended GPL, see docs/LICENSE */

namespace CaT\Filter\Filters;

class Multiselect extends Filter {
	/**
	 * @var	array
	 */
	private $options;

	/**
	 * @var	int[]|string[]
	 */
	private $default_choice;
	
	public function __construct(\CaT\Filter\FilterFactory $factory, $label, $description, $options,
								$default_choice = array(), array $mappings = array(),
								array $mapping_result_types = array()) {
		assert('is_string($label)');
		assert('is_string($description)');

		$this->setFactory($factory);
		$this->setLabel($label);
		$this->setDescription($description);
		$this->setMappings($mappings, $mapping_result_types);

		$keys = array_keys($options);
		$tf = $factory->type_factory();
		if ($tf->lst($tf->int())->contains($keys)) {
			$this->content_type = $tf->lst($tf->int());
		}
		else if ($tf->lst($tf->string())->contains($keys)) {
			$this->content_type = $tf->lst($tf->string());
		}
		else {
			throw new \InvalidArgumentException("Use only strings or only ints as keys for options.");
		}

		$this->options = $options;
		$this->default_choice = $default_choice;
	}

	/**
	 * @inheritdocs
	 */
	public function original_content_type() {
		return $this->content_type;
	}

	/**
	 * @inheritdocs
	 */
	public function input_type() {
		return $this->original_content_type();
	}

	/**
	 * @inheritdocs
	 */
	protected function raw_content($input) {
		return $input;
	}

	/**
	 * Get the options that could be selected.
	 *
	 * @return	int[]|string[]
	 */
	public function options() {
		return $this->options;
	}

	/**
	 * Set or get the default choice of options for the multiselect.
	 *
	 * @param	int[]|string[]|null		$options
	 * @return	Multiselect|string[]|int[]
	 */
	public function default_choice(array $options = null) {
		if ($options === null) {
			return $this->default_choice;
		}

		list($ms, $mrts) = $this->getMappings();
		return new Multiselect($this->factory, $this->label(), $this->description(),
						$options, $this->default_choice, $ms, $mrts);
	}

	/**
	 * @inheritdocs
	 */
	protected function clone_with_new_mappings($mappings, $mapping_result_types) {
		return new Multiselect($this->factory, $this->label(), $this->description(),
						$this->options, $this->default_choice, $mappings, $mapping_result_types);
	}
}
