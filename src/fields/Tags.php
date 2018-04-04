<?php
namespace verbb\feedme\fields;

use verbb\feedme\base\Field;
use verbb\feedme\base\FieldInterface;

use Craft;
use craft\elements\Tag as TagElement;

use Cake\Utility\Hash;

class Tags extends Field implements FieldInterface
{
    // Properties
    // =========================================================================

    public static $name = 'Tags';
    public static $class = 'craft\fields\Tags';


    // Templates
    // =========================================================================

    public function getMappingTemplate()
    {
        return 'feed-me/_includes/fields/tags';
    }


    // Public Methods
    // =========================================================================

    public function parseField()
    {
        $value = $this->fetchArrayValue();

        $settings = Hash::get($this->field, 'settings');
        $source = Hash::get($this->field, 'settings.source');
        $limit = Hash::get($this->field, 'settings.limit');
        $match = Hash::get($this->fieldInfo, 'options.match', 'title');
        $create = Hash::get($this->fieldInfo, 'options.create');
        $fields = Hash::get($this->fieldInfo, 'fields');

        // Get tag group id
        list($type, $groupId) = explode(':', $source);

        $foundElements = [];

        foreach ($value as $dataValue) {
            // Prevent empty or blank values (string or array), which match all elements
            if (empty($dataValue)) {
                continue;
            }
            
            $query = TagElement::find();

            $criteria['groupId'] = $groupId;
            $criteria['limit'] = $limit;
            $criteria[$match] = $dataValue;

            Craft::configure($query, $criteria);

            $ids = $query->ids();

            $foundElements = array_merge($foundElements, $ids);

            // Check if we should create the element. But only if title is provided (for the moment)
            if (count($ids) == 0) {
                if ($create && $match === 'title') {
                    $foundElements[] = $this->_createElement($dataValue, $groupId);
                }
            }
        }

        // Check for field limit - only return the specified amount
        if ($foundElements && $limit) {
            $foundElements = array_chunk($foundElements, $limit)[0];
        }

        // Check for any sub-fields for the lement
        if ($fields) {
            $this->populateElementFields($foundElements);
        }

        return $foundElements;
    }


    // Private Methods
    // =========================================================================

    private function _createElement($dataValue, $groupId)
    {
        $element = new TagElement();
        $element->title = $dataValue;
        $element->groupId = $groupId;

        if (!Craft::$app->getElements()->saveElement($element)) {
            throw new \Exception(json_encode($element->getErrors()));
        }

        return $element->id;
    }

}