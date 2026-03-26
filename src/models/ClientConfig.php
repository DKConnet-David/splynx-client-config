<?php

namespace splynx\client_config\models;

use yii\db\ActiveRecord;

/**
 * Stores the current rich-text configuration content for a customer.
 *
 * @property int    $id
 * @property int    $customer_id
 * @property string $content        HTML content from the rich text editor
 * @property int    $updated_by     Admin user ID who last modified this
 * @property string $updated_by_name Display name of the admin who last modified
 * @property string $created_at
 * @property string $updated_at
 */
class ClientConfig extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%client_config}}';
    }

    public function rules()
    {
        return [
            [['customer_id'], 'required'],
            [['customer_id', 'updated_by'], 'integer'],
            [['content'], 'string'],
            [['updated_by_name'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
            [['customer_id'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'customer_id' => 'Customer',
            'content' => 'Configuration Notes',
            'updated_by' => 'Last Modified By (ID)',
            'updated_by_name' => 'Last Modified By',
            'updated_at' => 'Last Modified',
        ];
    }

    /**
     * Get the audit history for this config entry.
     */
    public function getHistory()
    {
        return $this->hasMany(ClientConfigHistory::class, ['customer_id' => 'customer_id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Find or create a config record for the given customer.
     */
    public static function findOrCreate(int $customerId): self
    {
        $model = static::findOne(['customer_id' => $customerId]);
        if ($model === null) {
            $model = new static();
            $model->customer_id = $customerId;
            $model->content = '';
        }
        return $model;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }
}
