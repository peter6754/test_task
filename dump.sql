create table orders
(
    id                         int auto_increment primary key,
    hash                       varchar(32)              not null comment 'hash заказа',
    user_id                    int                      null,
    token                      varchar(64)              not null comment 'уникальный хеш пользователя',
    number                     varchar(10)              null comment 'Номер заказа',
    status                     int        default 1     not null comment 'Статус заказа',
    email                      varchar(100)             null comment 'контактный E-mail',
    vat_type                   int        default 0     not null comment 'Частное лицо или плательщик НДС',
    vat_number                 varchar(100)             null comment 'НДС-номер',
    tax_number                 varchar(50)              null comment 'Индивидуальный налоговый номер налогоплательщика',
    discount                   smallint                 null comment 'Процент скидки',
    delivery                   double                   null comment 'Стоимость доставки',
    delivery_type              smallint   default 0     null comment 'Тип доставки: 0 - адрес клинта, 1 - адрес склада',
    delivery_time_min          date                     null comment 'Минимальный срок доставки',
    delivery_time_max          date                     null comment 'Максимальный срок доставки',
    delivery_time_confirm_min  date                     null comment 'Минимальный срок доставки подтверждённый производителем',
    delivery_time_confirm_max  date                     null comment 'Максимальный срок доставки подтверждённый производителем',
    delivery_time_fast_pay_min date                     null comment 'Минимальный срок доставки',
    delivery_time_fast_pay_max date                     null comment 'Максимальный срок доставки',
    delivery_old_time_min      date                     null comment 'Прошлый минимальный срок доставки',
    delivery_old_time_max      date                     null comment 'Прошлый максимальный срок доставки',
    delivery_index             varchar(20)              null,
    delivery_country           int                      null,
    delivery_region            varchar(50)              null,
    delivery_city              varchar(200)             null,
    delivery_address           varchar(300)             null,
    delivery_building          varchar(200)             null,
    delivery_phone_code        varchar(20)              null,
    delivery_phone             varchar(20)              null,
    sex                        smallint                 null comment 'Пол клиента',
    client_name                varchar(255)             null comment 'Имя клиента',
    client_surname             varchar(255)             null comment 'Фамилия клиента',
    company_name               varchar(255)             null comment 'Название компании',
    pay_type                   smallint                 not null comment 'Выбранный тип оплаты',
    pay_date_execution         datetime                 null comment 'Дата до которой действует текущая цена заказа',
    offset_date                datetime                 null comment 'Дата сдвига предполагаемого расчета доставки',
    offset_reason              smallint                 null comment 'тип причина сдвига сроков 1 - каникулы на фабрике, 2 - фабрика уточняет сроки пр-ва, 3 - другое',
    proposed_date              datetime                 null comment 'Предполагаемая дата поставки',
    ship_date                  datetime                 null comment 'Предполагаемая дата отгрузки',
    tracking_number            varchar(50)              null comment 'Номер треккинга',
    manager_name               varchar(20)              null comment 'Имя менеджера сопровождающего заказ',
    manager_email              varchar(30)              null comment 'Email менеджера сопровождающего заказ',
    manager_phone              varchar(20)              null comment 'Телефон менеджера сопровождающего заказ',
    carrier_name               varchar(50)              null comment 'Название транспортной компании',
    carrier_contact_data       varchar(255)             null comment 'Контактные данные транспортной компании',
    locale                     varchar(5)               not null comment 'локаль из которой был оформлен заказ',
    cur_rate                   double     default 1     null comment 'курс на момент оплаты',
    currency                   varchar(3) default 'EUR' not null comment 'валюта при которой был оформлен заказ',
    measure                    varchar(3) default 'm'   not null comment 'ед. изм. в которой был оформлен заказ',
    name                       varchar(200)             not null comment 'Название заказа',
    description                varchar(1000)            null comment 'Дополнительная информация',
    create_date                datetime                 not null comment 'Дата создания',
    update_date                datetime                 null comment 'Дата изменения',
    warehouse_data             longtext                 null comment 'Данные склада: адрес, название, часы работы',
    step                       smallint   default 1     not null comment 'если true то заказ не будет сброшен в следствии изменений',
    address_equal              tinyint(1) default 1     null comment 'Адреса плательщика и получателя совпадают (false - разные, true - одинаковые )',
    bank_transfer_requested    tinyint(1)               null comment 'Запрашивался ли счет на банковский перевод',
    accept_pay                 tinyint(1)               null comment 'Если true то заказ отправлен в работу',
    cancel_date                datetime                 null comment 'Конечная дата согласования сроков поставки',
    weight_gross               double                   null comment 'Общий вес брутто заказа',
    product_review             tinyint(1)               null comment 'Оставлен отзыв по коллекциям в заказе',
    mirror                     smallint                 null comment 'Метка зеркала на котором создается заказ',
    process                    tinyint(1)               null comment 'метка массовой обработки',
    fact_date                  datetime                 null comment 'Фактическая дата поставки',
    entrance_review            smallint                 null comment 'Фиксирует вход клиента на страницу отзыва и последующие клики',
    payment_euro               tinyint(1) default 0     null comment 'Если true, то оплату посчитать в евро',
    spec_price                 tinyint(1)               null comment 'установлена спец цена по заказу',
    show_msg                   tinyint(1)               null comment 'Показывать спец. сообщение',
    delivery_price_euro        double                   null comment 'Стоимость доставки в евро',
    address_payer              int                      null,
    sending_date               datetime                 null comment 'Расчетная дата поставки',
    delivery_calculate_type    smallint   default 0     null comment 'Тип расчета: 0 - ручной, 1 - автоматический',
    full_payment_date          date                     null comment 'Дата полной оплаты заказа',
    bank_details               longtext                 null comment 'Реквизиты банка для возврата средств',
    delivery_apartment_office  varchar(30)              null comment 'Квартира/офис'
)
    comment 'Хранит информацию о заказах' avg_row_length = 2209;

create index IDX_1
    on orders (delivery_country);

create index IDX_2
    on orders (user_id);

create index IDX_3
    on orders (create_date);

create index IDX_4
    on orders (create_date, status);

create index IDX_5
    on orders (hash);

create table orders_article
(
    id                        int auto_increment primary key,
    orders_id                 int                  null,
    article_id                int                  null comment 'ID коллекции',
    amount                    double               not null comment 'количество артикулов в ед. измерения',
    price                     double               not null comment 'Цена на момент оплаты заказа',
    price_eur                 double               null comment 'Цена в Евро по заказу',
    currency                  varchar(3)           null comment 'Валюта для которой установлена цена',
    measure                   varchar(2)           null comment 'Ед. изм. для которой установлена цена',
    delivery_time_min         date                 null comment 'Минимальный срок доставки',
    delivery_time_max         date                 null comment 'Максимальный срок доставки',
    weight                    double               not null comment 'вес упаковки',
    multiple_pallet           smallint             null comment 'Кратность палете, 1 - кратно упаковке, 2 - кратно палете, 3 - не меньше палеты',
    packaging_count           double               not null comment 'Количество кратно которому можно добавлять товар в заказ',
    pallet                    double               not null comment 'количество в палете на момент заказа',
    packaging                 double               not null comment 'количество в упаковке',
    swimming_pool             tinyint(1) default 0 not null comment 'Плитка специально для бассейна'
)
    comment 'Хранит информацию об артикулах заказа' avg_row_length = 121;

create index IDX_318C0B7C7294869C
    on orders_article (article_id);

create index IDX_318C0B7C7FC358ED
    on orders_article (orders_id);