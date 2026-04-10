SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- База данных: `new_dump`
--

-- --------------------------------------------------------

--
-- Структура таблицы `carriers`
--

CREATE TABLE `carriers` (
    `id` int NOT NULL,
    `name` varchar(100) NOT NULL COMMENT 'Название транспортной компании',
    `contact_data` varchar(300) DEFAULT NULL COMMENT 'Контактные данные',
    `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Транспортные компании';

-- --------------------------------------------------------

--
-- Структура таблицы `countries`
--

CREATE TABLE `countries` (
    `id` int NOT NULL,
    `code` varchar(3) NOT NULL COMMENT 'ISO 3166-1 alpha-2/3',
    `name` varchar(100) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Справочник стран';

-- --------------------------------------------------------

--
-- Структура таблицы `managers`
--

CREATE TABLE `managers` (
    `id` int NOT NULL,
    `name` varchar(100) NOT NULL COMMENT 'Имя менеджера',
    `email` varchar(100) NOT NULL,
    `phone` varchar(30) DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Менеджеры, сопровождающие заказы';

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
    `id` int NOT NULL,
    `hash` varchar(32) NOT NULL COMMENT 'Уникальный хеш заказа',
    `token` varchar(64) NOT NULL COMMENT 'Уникальный токен сессии',
    `user_id` int DEFAULT NULL COMMENT 'FK → users.id',
    `number` varchar(20) DEFAULT NULL COMMENT 'Номер заказа (присваивается после подтверждения)',
    `name` varchar(200) NOT NULL COMMENT 'Название заказа',
    `description` varchar(1000) DEFAULT NULL COMMENT 'Дополнительная информация',
    `locale` varchar(5) NOT NULL COMMENT 'Локаль оформления заказа',
    `mirror` smallint DEFAULT NULL COMMENT 'Метка зеркала/сайта на котором создан заказ',
    `status` enum(
        'new',
        'confirmed',
        'paid',
        'in_production',
        'shipped',
        'delivered',
        'cancelled'
    ) NOT NULL DEFAULT 'new' COMMENT 'Статус заказа',
    `step` smallint NOT NULL DEFAULT '1' COMMENT 'Шаг оформления заказа',
    `accept_pay` tinyint(1) DEFAULT NULL COMMENT 'Заказ отправлен в работу',
    `process` tinyint(1) DEFAULT NULL COMMENT 'Метка массовой обработки',
    `show_msg` tinyint(1) DEFAULT NULL COMMENT 'Показывать спец. сообщение',
    `spec_price` tinyint(1) DEFAULT NULL COMMENT 'Установлена спец. цена',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата изменения'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Заказы — ядро';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_article`
--

CREATE TABLE `orders_article` (
    `id` int NOT NULL,
    `order_id` int NOT NULL COMMENT 'FK → orders.id',
    `article_id` int DEFAULT NULL COMMENT 'FK → articles.id (коллекция)',
    `amount` decimal(12, 4) NOT NULL COMMENT 'Количество в единицах измерения',
    `price` decimal(12, 2) NOT NULL COMMENT 'Цена на момент заказа',
    `price_eur` decimal(12, 2) DEFAULT NULL COMMENT 'Цена в евро',
    `currency` varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Валюта цены',
    `measure` varchar(3) NOT NULL DEFAULT 'm' COMMENT 'Единица измерения',
    `weight` decimal(10, 4) NOT NULL COMMENT 'Вес упаковки',
    `packaging_count` decimal(10, 4) NOT NULL COMMENT 'Минимальная кратность добавления',
    `pallet` decimal(10, 4) NOT NULL COMMENT 'Количество в палете',
    `packaging` decimal(10, 4) NOT NULL COMMENT 'Количество в упаковке',
    `multiple_pallet` enum(
        'by_package',
        'by_pallet',
        'min_pallet'
    ) DEFAULT NULL COMMENT 'Кратность палете',
    `swimming_pool` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Плитка для бассейна',
    `delivery_date_min` date DEFAULT NULL COMMENT 'Минимальный срок доставки артикула',
    `delivery_date_max` date DEFAULT NULL COMMENT 'Максимальный срок доставки артикула'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Артикулы (позиции) заказа';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_bank_details`
--

CREATE TABLE `orders_bank_details` (
    `id` int NOT NULL,
    `order_id` int NOT NULL,
    `bank_name` varchar(200) DEFAULT NULL COMMENT 'Название банка',
    `iban` varchar(34) DEFAULT NULL COMMENT 'IBAN',
    `bic` varchar(11) DEFAULT NULL COMMENT 'BIC/SWIFT',
    `account_holder` varchar(200) DEFAULT NULL COMMENT 'Владелец счёта',
    `extra` text COMMENT 'Дополнительные реквизиты'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Реквизиты банка для возврата средств';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_client`
--

CREATE TABLE `orders_client` (
    `id` int NOT NULL,
    `order_id` int NOT NULL,
    `sex` tinyint DEFAULT NULL COMMENT '0 - не указан, 1 - мужской, 2 - женский',
    `client_name` varchar(255) DEFAULT NULL COMMENT 'Имя клиента',
    `client_surname` varchar(255) DEFAULT NULL COMMENT 'Фамилия клиента',
    `company_name` varchar(255) DEFAULT NULL COMMENT 'Название компании',
    `email` varchar(100) DEFAULT NULL COMMENT 'Контактный e-mail',
    `vat_type` tinyint NOT NULL DEFAULT '0' COMMENT '0 - физлицо, 1 - плательщик НДС',
    `vat_number` varchar(100) DEFAULT NULL COMMENT 'НДС-номер',
    `tax_number` varchar(50) DEFAULT NULL COMMENT 'ИНН',
    `address_payer` int DEFAULT NULL COMMENT 'FK → addresses.id (адрес плательщика)'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Данные клиента/плательщика по заказу';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_delivery`
--

CREATE TABLE `orders_delivery` (
    `id` int NOT NULL,
    `order_id` int NOT NULL,
    `warehouse_id` int DEFAULT NULL COMMENT 'FK → warehouses.id (если доставка на склад)',
    `carrier_id` int DEFAULT NULL COMMENT 'FK → carriers.id',
    `delivery_type` enum('client_address', 'warehouse') NOT NULL DEFAULT 'client_address' COMMENT 'Тип доставки',
    `delivery_calculate_type` enum('manual', 'auto') NOT NULL DEFAULT 'manual' COMMENT 'Тип расчёта стоимости',
    `price` decimal(12, 2) DEFAULT NULL COMMENT 'Стоимость доставки',
    `price_eur` decimal(12, 2) DEFAULT NULL COMMENT 'Стоимость доставки в евро',
    `tracking_number` varchar(50) DEFAULT NULL COMMENT 'Номер трекинга',
    `address_equal` tinyint(1) DEFAULT '1' COMMENT 'Адрес плательщика = адрес получателя',
    `country_id` int DEFAULT NULL COMMENT 'FK → countries.id',
    `region` varchar(100) DEFAULT NULL,
    `city` varchar(200) DEFAULT NULL,
    `address` varchar(300) DEFAULT NULL,
    `building` varchar(200) DEFAULT NULL,
    `apartment_office` varchar(30) DEFAULT NULL COMMENT 'Квартира/офис',
    `postal_index` varchar(20) DEFAULT NULL,
    `phone_code` varchar(20) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Информация о доставке по заказу';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_delivery_dates`
--

CREATE TABLE `orders_delivery_dates` (
    `id` int NOT NULL,
    `order_id` int NOT NULL,
    `type` enum(
        'estimated',
        'confirmed',
        'fast_pay',
        'old',
        'proposed',
        'sending',
        'ship',
        'fact'
    ) NOT NULL COMMENT 'Тип даты',
    `date_min` date DEFAULT NULL,
    `date_max` date DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Даты доставки по типам';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_manager`
--

CREATE TABLE `orders_manager` (
    `id` int NOT NULL,
    `order_id` int NOT NULL,
    `manager_id` int NOT NULL,
    `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Менеджер, сопровождающий заказ';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_meta`
--

CREATE TABLE `orders_meta` (
    `id` int NOT NULL,
    `order_id` int NOT NULL,
    `weight_gross` double DEFAULT NULL COMMENT 'Общий вес брутто заказа',
    `measure` varchar(3) NOT NULL DEFAULT 'm' COMMENT 'Единица измерения заказа',
    `offset_date` datetime DEFAULT NULL COMMENT 'Дата сдвига расчёта доставки',
    `offset_reason` enum(
        'factory_holiday',
        'factory_clarifying',
        'other'
    ) DEFAULT NULL COMMENT 'Причина сдвига сроков',
    `cancel_date` datetime DEFAULT NULL COMMENT 'Крайняя дата согласования сроков',
    `proposed_date` datetime DEFAULT NULL COMMENT 'Предполагаемая дата поставки',
    `product_review` tinyint(1) DEFAULT NULL COMMENT 'Оставлен отзыв по коллекциям',
    `entrance_review` smallint DEFAULT NULL COMMENT 'Вход клиента на страницу отзыва'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Служебные и вспомогательные метаданные заказа';

-- --------------------------------------------------------

--
-- Структура таблицы `orders_payment`
--

CREATE TABLE `orders_payment` (
    `id` int NOT NULL,
    `order_id` int NOT NULL,
    `pay_type` smallint NOT NULL COMMENT 'Выбранный тип оплаты',
    `currency` varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Валюта заказа',
    `cur_rate` decimal(10, 6) NOT NULL DEFAULT '1.000000' COMMENT 'Курс на момент оплаты',
    `payment_euro` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Считать оплату в евро',
    `discount` smallint DEFAULT NULL COMMENT 'Процент скидки',
    `pay_date_execution` datetime DEFAULT NULL COMMENT 'Дата до которой действует текущая цена',
    `full_payment_date` date DEFAULT NULL COMMENT 'Дата полной оплаты',
    `bank_transfer_requested` tinyint(1) DEFAULT NULL COMMENT 'Запрашивался ли счёт на банковский перевод'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Платёжная информация по заказу';

-- --------------------------------------------------------

--
-- Структура таблицы `warehouses`
--

CREATE TABLE `warehouses` (
    `id` int NOT NULL,
    `name` varchar(200) NOT NULL COMMENT 'Название склада',
    `address` varchar(300) NOT NULL COMMENT 'Адрес',
    `working_hours` varchar(200) DEFAULT NULL COMMENT 'Часы работы',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = 'Склады';

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `carriers`
--
ALTER TABLE `carriers` ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `countries`
--
ALTER TABLE `countries`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_code` (`code`);

--
-- Индексы таблицы `managers`
--
ALTER TABLE `managers`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_email` (`email`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_hash` (`hash`),
ADD UNIQUE KEY `UQ_token` (`token`),
ADD KEY `IDX_user_id` (`user_id`),
ADD KEY `IDX_status` (`status`),
ADD KEY `IDX_created_at` (`created_at`),
ADD KEY `IDX_status_created` (`status`, `created_at`);

--
-- Индексы таблицы `orders_article`
--
ALTER TABLE `orders_article`
ADD PRIMARY KEY (`id`),
ADD KEY `IDX_order_id` (`order_id`),
ADD KEY `IDX_article_id` (`article_id`);

--
-- Индексы таблицы `orders_bank_details`
--
ALTER TABLE `orders_bank_details`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_order_id` (`order_id`);

--
-- Индексы таблицы `orders_client`
--
ALTER TABLE `orders_client`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_order_id` (`order_id`);

--
-- Индексы таблицы `orders_delivery`
--
ALTER TABLE `orders_delivery`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_order_id` (`order_id`),
ADD KEY `FK_orders_delivery_warehouse` (`warehouse_id`),
ADD KEY `FK_orders_delivery_carrier` (`carrier_id`),
ADD KEY `IDX_country_id` (`country_id`);

--
-- Индексы таблицы `orders_delivery_dates`
--
ALTER TABLE `orders_delivery_dates`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_order_type` (`order_id`, `type`),
ADD KEY `IDX_order_id` (`order_id`);

--
-- Индексы таблицы `orders_manager`
--
ALTER TABLE `orders_manager`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_order_id` (`order_id`),
ADD KEY `IDX_manager_id` (`manager_id`);

--
-- Индексы таблицы `orders_meta`
--
ALTER TABLE `orders_meta`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_order_id` (`order_id`);

--
-- Индексы таблицы `orders_payment`
--
ALTER TABLE `orders_payment`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `UQ_order_id` (`order_id`);

--
-- Индексы таблицы `warehouses`
--
ALTER TABLE `warehouses` ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `carriers`
--
ALTER TABLE `carriers` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `countries`
--
ALTER TABLE `countries` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `managers`
--
ALTER TABLE `managers` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_article`
--
ALTER TABLE `orders_article` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_bank_details`
--
ALTER TABLE `orders_bank_details`
MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_client`
--
ALTER TABLE `orders_client` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_delivery`
--
ALTER TABLE `orders_delivery`
MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_delivery_dates`
--
ALTER TABLE `orders_delivery_dates`
MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_manager`
--
ALTER TABLE `orders_manager` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_meta`
--
ALTER TABLE `orders_meta` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders_payment`
--
ALTER TABLE `orders_payment` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `warehouses`
--
ALTER TABLE `warehouses` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders_article`
--
ALTER TABLE `orders_article`
ADD CONSTRAINT `FK_orders_article_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders_bank_details`
--
ALTER TABLE `orders_bank_details`
ADD CONSTRAINT `FK_bank_details_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders_client`
--
ALTER TABLE `orders_client`
ADD CONSTRAINT `FK_orders_client_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders_delivery`
--
ALTER TABLE `orders_delivery`
ADD CONSTRAINT `FK_orders_delivery_carrier` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `FK_orders_delivery_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `FK_orders_delivery_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `FK_orders_delivery_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `orders_delivery_dates`
--
ALTER TABLE `orders_delivery_dates`
ADD CONSTRAINT `FK_delivery_dates_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders_manager`
--
ALTER TABLE `orders_manager`
ADD CONSTRAINT `FK_orders_manager_manager` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`) ON DELETE RESTRICT,
ADD CONSTRAINT `FK_orders_manager_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders_meta`
--
ALTER TABLE `orders_meta`
ADD CONSTRAINT `FK_orders_meta_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders_payment`
--
ALTER TABLE `orders_payment`
ADD CONSTRAINT `FK_orders_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;