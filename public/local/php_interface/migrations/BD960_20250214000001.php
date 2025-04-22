<?php

namespace Sprint\Migration;

use Bitrix\Iblock\PropertyTable;
use Throwable;

/**
 * @see https://jira.cargonomica.com/browse/BD-960
 */
class BD960_20250214000001 extends Version
{
    protected const string IBLOCK_TYPE_ID = 'CARGONOMICA_ABOUT';

    protected $author = "v.andreev";

    protected $description = "Миграция на ИБ 'О компании - Оптимизация сегодня'";

    protected $moduleVersion = "4.15.1";

    protected array $iblockData = [
        'IBLOCK_TYPE_ID' => self::IBLOCK_TYPE_ID,
        'NAME' => 'Cargonomica - О компании - Оптимизация сегодня',
        'CODE' => 'CARGONOMICA_ABOUT_OPTIMIZATION',
        'LID' => ['s1'],
    ];

    protected array $iblockPropertiesData = [
        [
            'NAME' => 'Заголовок',
            'ACTIVE' => 'Y',
            'CODE' => 'TITLE_OPTIMIZATION',
            'SORT' => 100,
            'PROPERTY_TYPE' => PropertyTable::TYPE_STRING,
            'IS_REQUIRED' => 'Y',
            'MULTIPLE' => 'N',
        ],
        [
            'NAME' => 'Текст',
            'ACTIVE' => 'Y',
            'CODE' => 'TEXT_OPTIMIZATION',
            'SORT' => 200,
            'PROPERTY_TYPE' => PropertyTable::TYPE_STRING,
            'IS_REQUIRED' => 'N',
            'MULTIPLE' => 'N',
        ],
    ];

    protected array $iblockElementFormData = [
        'Параметры|edit1' => [
            'ID' => 'ID',
            'NAME' => 'Название',
            'CODE' => 'Символьный код',
            'ACTIVE' => 'Активность',
            'DATE_CREATE' => 'Создан',
            'TIMESTAMP_X' => 'Изменен',
            'SORT' => 'Сортировка',

            'PROPERTY_TITLE_OPTIMIZATION' => 'Заголовок',
            'PROPERTY_TEXT_OPTIMIZATION' => 'Текст',
        ],
    ];

    protected array $iblockPermissionsData = [
        'administrators' => 'X',
        CONTENT_EDITOR_UG_ID => 'W',
        'everyone' => 'R',
    ];

    public function up(): bool
    {
        try {
            $helper = $this->getHelperManager();

            // Создаем инфоблок
            $iblockId = $helper->Iblock()->addIblockIfNotExists($this->iblockData);
            $this->outInfo("Создан инфоблок {$this->iblockData['CODE']}");

            // Применяем настройки групповых прав для инфоблока
            $helper->Iblock()->saveGroupPermissions($iblockId, $this->iblockPermissionsData);
            $this->outInfo("Применены права доступа к инфоблоку ID={$iblockId}");

            // Создаем свойства инфоблока
            foreach ($this->iblockPropertiesData as $propertyData) {
                $helper->Iblock()->addPropertyIfNotExists(
                    $iblockId,
                    $propertyData,
                );
                $this->outInfo("Добавлено свойство инфоблока {$propertyData['CODE']}");
            }

            // Сохраняем настройки формы редактирования элементов инфоблока
            $helper->UserOptions()->saveElementForm($iblockId, $this->iblockElementFormData);
            $this->outInfo("Применены настройки формы редактирования элементов инфоблока ID={$iblockId}");

            $this->outSuccess("Установка миграции на инфоблок `" . $this->iblockData['CODE'] . "` прошла успешно.");

            return true;
        } catch (Throwable $e) {
            $this->outError("Не удалось установить миграцию.");
            $this->outException($e);

            return false;
        }
    }

    public function down(): bool
    {
        try {
            $helper = $this->getHelperManager();

            // Удаляем инфоблок
            $helper->Iblock()->deleteIblockIfExists($this->iblockData['CODE']);
            $this->outInfo("Удален инфоблок {$this->iblockData['CODE']}");

            $this->outSuccess("Откат миграции на инфоблок `" . $this->iblockData['CODE'] . "` прошел успешно.");

            return true;
        } catch (Throwable $e) {
            $this->outError("Не задалось откатить миграцию.");
            $this->outException($e);

            return false;
        }
    }
}
