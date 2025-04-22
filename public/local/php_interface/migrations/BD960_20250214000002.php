<?php

namespace Sprint\Migration;

use Bitrix\Iblock\ElementTable;
use CIBlockElement;
use Exception;
use Sprint\Migration\Exceptions\MigrationException;
use Throwable;

/**
 * @see https://jira.cargonomica.com/browse/BD-960
 */
class BD960_20250214000002 extends Version
{
    protected $author = "v.andreev";

    protected $description = "Добавление тестовых данных в ИБ `О компании - Оптимизация сегодня`";

    protected $moduleVersion = "4.15.1";

    protected const string IBLOCK_TYPE_ID = 'CARGONOMICA_ABOUT';

    protected const string IBLOCK_CODE = 'CARGONOMICA_ABOUT_OPTIMIZATION';

    /** @var array Тестовые данные для добавления в инфоблок */
    protected array $elementsData = [
        [
            'NAME' => 'Транспортных пользуются',
            'CODE' => 'FIRST_ELEMENT',
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'TITLE_OPTIMIZATION' => '2149',
                'TEXT_OPTIMIZATION' => 'Транспортных компаний пользуются нашими продуктами',
            ],
        ],
        [
            'NAME' => 'Сцепок подключено',
            'CODE' => 'SECOND_ELEMENT',
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'TITLE_OPTIMIZATION' => '13676',
                'TEXT_OPTIMIZATION' => 'Сцепок подключено к экосистеме',
            ],
        ],
        [
            'NAME' => 'Соотрудников в команде',
            'CODE' => 'THIRD_ELEMENT',
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'TITLE_OPTIMIZATION' => '74',
                'TEXT_OPTIMIZATION' => 'Соотрудников в команде',
            ],
        ],
    ];

    /**
     * @return bool
     */
    public function up(): bool
    {
        try {
            $helper = $this->getHelperManager();
            $iblockId = $this->getIblockId($helper);

            foreach ($this->elementsData as $elementData) {
                $helper->Iblock()->addElementIfNotExists($iblockId, $elementData);
                $this->outInfo("Добавлен элемент {$elementData['CODE']}");
            }

            $this->outSuccess($this->description . "\nУстановка прошла успешно.");

            return true;
        } catch (Throwable $e) {
            $this->outError($this->description . "\nНе удалось установить.");
            $this->outException($e);

            return false;
        }
    }

    /**
     * @param HelperManager $helper
     * @return int
     * @throws MigrationException
     */
    protected function getIblockId(HelperManager $helper): int
    {
        $iblockId = $helper->Iblock()->getIblockId(self::IBLOCK_CODE);

        if (!$iblockId) {
            throw new MigrationException("Не найден инфоблок " . self::IBLOCK_CODE);
        }

        return $iblockId;
    }

    /**
     * @return bool
     */
    public function down(): bool
    {
        try {
            $helper = $this->getHelperManager();

            $iblock = $helper->Iblock()->getIblock([
                'IBLOCK_TYPE_ID' => self::IBLOCK_TYPE_ID,
                'CODE' => self::IBLOCK_CODE,
            ]);

            if (!$iblock) {
                $this->outError("Инфоблок не найден.");
                return false;
            }

            $elementCodes = array_column($this->elementsData, 'CODE');

            $elements = ElementTable::getList([
                'filter' => [
                    'IBLOCK_ID' => $iblock['ID'],
                    'CODE' => $elementCodes,
                ],
                'select' => [
                    'ID',
                    'CODE',
                ],
            ])->fetchAll();

            $elementMap = array_column($elements, 'ID', 'CODE');

            foreach ($elementCodes as $elementCode) {
                if (!isset($elementMap[$elementCode])) {
                    throw new Exception("Элемент с CODE {$elementCode} не найден.");
                }

                if (CIBlockElement::Delete($elementMap[$elementCode])) {
                    $this->outInfo("Элемент с CODE {$elementCode} успешно удалён.");
                } else {
                    throw new Exception("Не удалось удалить элемент с CODE {$elementCode}.");
                }
            }

            $this->outSuccess("Откат тестовых данных в ИБ завершён.");
            return true;
        } catch (Exception $e) {
            $this->outError("Ошибка при откате миграции.");
            $this->outException($e);
            return false;
        }
    }
}
