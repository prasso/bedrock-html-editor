<?php

namespace Prasso\BedrockHtmlEditor\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BedrockHtmlEditorModel extends Model
{
    /**
     * Create a new BedrockHtmlEditorModel instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // If the table name doesn't already have the prefix, add it
        if (!str_starts_with($this->getTable(), 'bhe_')) {
            $this->setTable('bhe_' . $this->getTable());
        }
    }
}
