<?php
class Migrate_2016_01_14_163721_658 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `global_lu_countries` ADD COLUMN `abbreviation` varchar(3);
        ALTER TABLE `global_lu_countries` ADD COLUMN `iso2` varchar(2);
        ALTER TABLE `global_lu_countries` ADD COLUMN `isonum` int(6);
        
        UPDATE `global_lu_countries` SET `country` = 'Afghanistan', `abbreviation` = 'AFG', `iso2` = 'AF', `isonum` = 4 WHERE `countries_id` = 1;
        UPDATE `global_lu_countries` SET `country` = 'Aland Islands', `abbreviation` = 'ALA', `iso2` = 'AX', `isonum` = 248 WHERE `countries_id` = 2;
        UPDATE `global_lu_countries` SET `country` = 'Albania', `abbreviation` = 'ALB', `iso2` = 'AL', `isonum` = 8 WHERE `countries_id` = 3;
        UPDATE `global_lu_countries` SET `country` = 'Algeria', `abbreviation` = 'DZA', `iso2` = 'DZ', `isonum` = 12 WHERE `countries_id` = 4;
        UPDATE `global_lu_countries` SET `country` = 'American Samoa', `abbreviation` = 'ASM', `iso2` = 'AS', `isonum` = 16 WHERE `countries_id` = 5;
        UPDATE `global_lu_countries` SET `country` = 'Andorra', `abbreviation` = 'AND', `iso2` = 'AD', `isonum` = 20 WHERE `countries_id` = 6;
        UPDATE `global_lu_countries` SET `country` = 'Angola', `abbreviation` = 'AGO', `iso2` = 'AO', `isonum` = 24 WHERE `countries_id` = 7;
        UPDATE `global_lu_countries` SET `country` = 'Anguilla', `abbreviation` = 'AIA', `iso2` = 'AI', `isonum` = 660 WHERE `countries_id` = 8;
        UPDATE `global_lu_countries` SET `country` = 'Antarctica', `abbreviation` = 'ATA', `iso2` = 'AQ', `isonum` = 10 WHERE `countries_id` = 9;
        UPDATE `global_lu_countries` SET `country` = 'Antigua and Barbuda', `abbreviation` = 'ATG', `iso2` = 'AG', `isonum` = 28 WHERE `countries_id` = 10;
        UPDATE `global_lu_countries` SET `country` = 'Argentina', `abbreviation` = 'ARG', `iso2` = 'AR', `isonum` = 32 WHERE `countries_id` = 11;
        UPDATE `global_lu_countries` SET `country` = 'Armenia', `abbreviation` = 'ARM', `iso2` = 'AM', `isonum` = 51 WHERE `countries_id` = 12;
        UPDATE `global_lu_countries` SET `country` = 'Aruba', `abbreviation` = 'ABW', `iso2` = 'AW', `isonum` = 533 WHERE `countries_id` = 13;
        UPDATE `global_lu_countries` SET `country` = 'Australia', `abbreviation` = 'AUS', `iso2` = 'AU', `isonum` = 36 WHERE `countries_id` = 14;
        UPDATE `global_lu_countries` SET `country` = 'Austria', `abbreviation` = 'AUT', `iso2` = 'AT', `isonum` = 40 WHERE `countries_id` = 15;
        UPDATE `global_lu_countries` SET `country` = 'Azerbaijan', `abbreviation` = 'AZE', `iso2` = 'AZ', `isonum` = 31 WHERE `countries_id` = 16;
        UPDATE `global_lu_countries` SET `country` = 'Bahamas', `abbreviation` = 'BHS', `iso2` = 'BS', `isonum` = 44 WHERE `countries_id` = 17;
        UPDATE `global_lu_countries` SET `country` = 'Bahrain', `abbreviation` = 'BHR', `iso2` = 'BH', `isonum` = 48 WHERE `countries_id` = 18;
        UPDATE `global_lu_countries` SET `country` = 'Bangladesh', `abbreviation` = 'BGD', `iso2` = 'BD', `isonum` = 50 WHERE `countries_id` = 19;
        UPDATE `global_lu_countries` SET `country` = 'Barbados', `abbreviation` = 'BRB', `iso2` = 'BB', `isonum` = 52 WHERE `countries_id` = 20;
        UPDATE `global_lu_countries` SET `country` = 'Belarus', `abbreviation` = 'BLR', `iso2` = 'BY', `isonum` = 112 WHERE `countries_id` = 21;
        UPDATE `global_lu_countries` SET `country` = 'Belgium', `abbreviation` = 'BEL', `iso2` = 'BE', `isonum` = 56 WHERE `countries_id` = 22;
        UPDATE `global_lu_countries` SET `country` = 'Belize', `abbreviation` = 'BLZ', `iso2` = 'BZ', `isonum` = 84 WHERE `countries_id` = 23;
        UPDATE `global_lu_countries` SET `country` = 'Benin', `abbreviation` = 'BEN', `iso2` = 'BJ', `isonum` = 204 WHERE `countries_id` = 24;
        UPDATE `global_lu_countries` SET `country` = 'Bermuda', `abbreviation` = 'BMU', `iso2` = 'BM', `isonum` = 60 WHERE `countries_id` = 25;
        UPDATE `global_lu_countries` SET `country` = 'Bhutan', `abbreviation` = 'BTN', `iso2` = 'BT', `isonum` = 64 WHERE `countries_id` = 26;
        UPDATE `global_lu_countries` SET `country` = 'Bolivia', `abbreviation` = 'BOL', `iso2` = 'BO', `isonum` = 68 WHERE `countries_id` = 27;
        UPDATE `global_lu_countries` SET `country` = 'Bosnia and Herzegovina', `abbreviation` = 'BIH', `iso2` = 'BA', `isonum` = 70 WHERE `countries_id` = 28;
        UPDATE `global_lu_countries` SET `country` = 'Botswana', `abbreviation` = 'BWA', `iso2` = 'BW', `isonum` = 72 WHERE `countries_id` = 29;
        UPDATE `global_lu_countries` SET `country` = 'Bouvet Island', `abbreviation` = 'BVT', `iso2` = 'BV', `isonum` = 74 WHERE `countries_id` = 30;
        UPDATE `global_lu_countries` SET `country` = 'Brazil', `abbreviation` = 'BRA', `iso2` = 'BR', `isonum` = 76 WHERE `countries_id` = 31;
        UPDATE `global_lu_countries` SET `country` = 'British Indian Ocean territory', `abbreviation` = 'IOT', `iso2` = 'IO', `isonum` = 86 WHERE `countries_id` = 32;
        UPDATE `global_lu_countries` SET `country` = 'Brunei Darussalam', `abbreviation` = 'BRN', `iso2` = 'BN', `isonum` = 96 WHERE `countries_id` = 33;
        UPDATE `global_lu_countries` SET `country` = 'Bulgaria', `abbreviation` = 'BGR', `iso2` = 'BG', `isonum` = 100 WHERE `countries_id` = 34;
        UPDATE `global_lu_countries` SET `country` = 'Burkina Faso', `abbreviation` = 'BFA', `iso2` = 'BF', `isonum` = 854 WHERE `countries_id` = 35;
        UPDATE `global_lu_countries` SET `country` = 'Burundi', `abbreviation` = 'BDI', `iso2` = 'BI', `isonum` = 108 WHERE `countries_id` = 36;
        UPDATE `global_lu_countries` SET `country` = 'Cambodia', `abbreviation` = 'KHM', `iso2` = 'KH', `isonum` = 116 WHERE `countries_id` = 37;
        UPDATE `global_lu_countries` SET `country` = 'Cameroon', `abbreviation` = 'CMR', `iso2` = 'CM', `isonum` = 120 WHERE `countries_id` = 38;
        UPDATE `global_lu_countries` SET `country` = 'Canada', `abbreviation` = 'CAN', `iso2` = 'CA', `isonum` = 124 WHERE `countries_id` = 39;
        UPDATE `global_lu_countries` SET `country` = 'Cape Verde', `abbreviation` = 'CPV', `iso2` = 'CV', `isonum` = 132 WHERE `countries_id` = 40;
        UPDATE `global_lu_countries` SET `country` = 'Cayman Islands', `abbreviation` = 'CYM', `iso2` = 'KY', `isonum` = 136 WHERE `countries_id` = 41;
        UPDATE `global_lu_countries` SET `country` = 'Central African Republic', `abbreviation` = 'CAF', `iso2` = 'CF', `isonum` = 140 WHERE `countries_id` = 42;
        UPDATE `global_lu_countries` SET `country` = 'Chad', `abbreviation` = 'TCD', `iso2` = 'TD', `isonum` = 148 WHERE `countries_id` = 43;
        UPDATE `global_lu_countries` SET `country` = 'Chile', `abbreviation` = 'CHL', `iso2` = 'CL', `isonum` = 152 WHERE `countries_id` = 44;
        UPDATE `global_lu_countries` SET `country` = 'China', `abbreviation` = 'CHN', `iso2` = 'CN', `isonum` = 156 WHERE `countries_id` = 45;
        UPDATE `global_lu_countries` SET `country` = 'Christmas Island', `abbreviation` = 'CXR', `iso2` = 'CX', `isonum` = 162 WHERE `countries_id` = 46;
        UPDATE `global_lu_countries` SET `country` = 'Cocos (Keeling) Islands', `abbreviation` = 'CCK', `iso2` = 'CC', `isonum` = 166 WHERE `countries_id` = 47;
        UPDATE `global_lu_countries` SET `country` = 'Colombia', `abbreviation` = 'COL', `iso2` = 'CO', `isonum` = 170 WHERE `countries_id` = 48;
        UPDATE `global_lu_countries` SET `country` = 'Comoros', `abbreviation` = 'COM', `iso2` = 'KM', `isonum` = 174 WHERE `countries_id` = 49;
        UPDATE `global_lu_countries` SET `country` = 'Congo', `abbreviation` = 'COG', `iso2` = 'CG', `isonum` = 178 WHERE `countries_id` = 50;
        UPDATE `global_lu_countries` SET `country` = 'Congo', `abbreviation` = 'COG', `iso2` = 'CG', `isonum` = 178 WHERE `countries_id` = 51;
        UPDATE `global_lu_countries` SET `country` = 'Democratic Republic', `abbreviation` = 'COD', `iso2` = 'CD', `isonum` = 180 WHERE `countries_id` = 52;
        UPDATE `global_lu_countries` SET `country` = 'Cook Islands', `abbreviation` = 'COK', `iso2` = 'CK', `isonum` = 184 WHERE `countries_id` = 53;
        UPDATE `global_lu_countries` SET `country` = 'Costa Rica', `abbreviation` = 'CRI', `iso2` = 'CR', `isonum` = 188 WHERE `countries_id` = 54;
        UPDATE `global_lu_countries` SET `country` = 'Cote D\'Ivoire (Ivory Coast)', `abbreviation` = 'CIV', `iso2` = 'CI', `isonum` = 384 WHERE `countries_id` = 55;
        UPDATE `global_lu_countries` SET `country` = 'Croatia (Hrvatska)', `abbreviation` = 'HRV', `iso2` = 'HR', `isonum` = 191 WHERE `countries_id` = 56;
        UPDATE `global_lu_countries` SET `country` = 'Cuba', `abbreviation` = 'CUB', `iso2` = 'CU', `isonum` = 192 WHERE `countries_id` = 57;
        UPDATE `global_lu_countries` SET `country` = 'Cyprus', `abbreviation` = 'CYP', `iso2` = 'CY', `isonum` = 196 WHERE `countries_id` = 58;
        UPDATE `global_lu_countries` SET `country` = 'Czech Republic', `abbreviation` = 'CZE', `iso2` = 'CZ', `isonum` = 203 WHERE `countries_id` = 59;
        UPDATE `global_lu_countries` SET `country` = 'Denmark', `abbreviation` = 'DNK', `iso2` = 'DK', `isonum` = 208 WHERE `countries_id` = 60;
        UPDATE `global_lu_countries` SET `country` = 'Djibouti', `abbreviation` = 'DJI', `iso2` = 'DJ', `isonum` = 262 WHERE `countries_id` = 61;
        UPDATE `global_lu_countries` SET `country` = 'Dominica', `abbreviation` = 'DMA', `iso2` = 'DM', `isonum` = 212 WHERE `countries_id` = 62;
        UPDATE `global_lu_countries` SET `country` = 'Dominican Republic', `abbreviation` = 'DOM', `iso2` = 'DO', `isonum` = 214 WHERE `countries_id` = 63;
        UPDATE `global_lu_countries` SET `country` = 'Timor-Leste', `abbreviation` = 'TLS', `iso2` = 'TL', `isonum` = 626 WHERE `countries_id` = 64;
        UPDATE `global_lu_countries` SET `country` = 'Ecuador', `abbreviation` = 'ECU', `iso2` = 'EC', `isonum` = 218 WHERE `countries_id` = 65;
        UPDATE `global_lu_countries` SET `country` = 'Egypt', `abbreviation` = 'EGY', `iso2` = 'EG', `isonum` = 818 WHERE `countries_id` = 66;
        UPDATE `global_lu_countries` SET `country` = 'El Salvador', `abbreviation` = 'SLV', `iso2` = 'SV', `isonum` = 222 WHERE `countries_id` = 67;
        UPDATE `global_lu_countries` SET `country` = 'Equatorial Guinea', `abbreviation` = 'GNQ', `iso2` = 'GQ', `isonum` = 226 WHERE `countries_id` = 68;
        UPDATE `global_lu_countries` SET `country` = 'Eritrea', `abbreviation` = 'ERI', `iso2` = 'ER', `isonum` = 232 WHERE `countries_id` = 69;
        UPDATE `global_lu_countries` SET `country` = 'Estonia', `abbreviation` = 'EST', `iso2` = 'EE', `isonum` = 233 WHERE `countries_id` = 70;
        UPDATE `global_lu_countries` SET `country` = 'Ethiopia', `abbreviation` = 'ETH', `iso2` = 'ET', `isonum` = 231 WHERE `countries_id` = 71;
        UPDATE `global_lu_countries` SET `country` = 'Falkland Islands', `abbreviation` = 'FLK', `iso2` = 'FK', `isonum` = 238 WHERE `countries_id` = 72;
        UPDATE `global_lu_countries` SET `country` = 'Faroe Islands', `abbreviation` = 'FRO', `iso2` = 'FO', `isonum` = 234 WHERE `countries_id` = 73;
        UPDATE `global_lu_countries` SET `country` = 'Fiji', `abbreviation` = 'FJI', `iso2` = 'FJ', `isonum` = 242 WHERE `countries_id` = 74;
        UPDATE `global_lu_countries` SET `country` = 'Finland', `abbreviation` = 'FIN', `iso2` = 'FI', `isonum` = 246 WHERE `countries_id` = 75;
        UPDATE `global_lu_countries` SET `country` = 'France', `abbreviation` = 'FRA', `iso2` = 'FR', `isonum` = 250 WHERE `countries_id` = 76;
        UPDATE `global_lu_countries` SET `country` = 'French Guiana', `abbreviation` = 'GUF', `iso2` = 'GF', `isonum` = 254 WHERE `countries_id` = 77;
        UPDATE `global_lu_countries` SET `country` = 'French Polynesia', `abbreviation` = 'PYF', `iso2` = 'PF', `isonum` = 258 WHERE `countries_id` = 78;
        UPDATE `global_lu_countries` SET `country` = 'French Southern Territories', `abbreviation` = 'ATF', `iso2` = 'TF', `isonum` = 260 WHERE `countries_id` = 79;
        UPDATE `global_lu_countries` SET `country` = 'Gabon', `abbreviation` = 'GAB', `iso2` = 'GA', `isonum` = 266 WHERE `countries_id` = 80;
        UPDATE `global_lu_countries` SET `country` = 'Gambia', `abbreviation` = 'GMB', `iso2` = 'GM', `isonum` = 270 WHERE `countries_id` = 81;
        UPDATE `global_lu_countries` SET `country` = 'Georgia', `abbreviation` = 'GEO', `iso2` = 'GE', `isonum` = 268 WHERE `countries_id` = 82;
        UPDATE `global_lu_countries` SET `country` = 'Germany', `abbreviation` = 'DEU', `iso2` = 'DE', `isonum` = 276 WHERE `countries_id` = 83;
        UPDATE `global_lu_countries` SET `country` = 'Ghana', `abbreviation` = 'GHA', `iso2` = 'GH', `isonum` = 288 WHERE `countries_id` = 84;
        UPDATE `global_lu_countries` SET `country` = 'Gibraltar', `abbreviation` = 'GIB', `iso2` = 'GI', `isonum` = 292 WHERE `countries_id` = 85;
        UPDATE `global_lu_countries` SET `country` = 'Greece', `abbreviation` = 'GRC', `iso2` = 'GR', `isonum` = 300 WHERE `countries_id` = 86;
        UPDATE `global_lu_countries` SET `country` = 'Greenland', `abbreviation` = 'GRL', `iso2` = 'GL', `isonum` = 304 WHERE `countries_id` = 87;
        UPDATE `global_lu_countries` SET `country` = 'Grenada', `abbreviation` = 'GRD', `iso2` = 'GD', `isonum` = 308 WHERE `countries_id` = 88;
        UPDATE `global_lu_countries` SET `country` = 'Guadeloupe', `abbreviation` = 'GLP', `iso2` = 'GP', `isonum` = 312 WHERE `countries_id` = 89;
        UPDATE `global_lu_countries` SET `country` = 'Guam', `abbreviation` = 'GUM', `iso2` = 'GU', `isonum` = 316 WHERE `countries_id` = 90;
        UPDATE `global_lu_countries` SET `country` = 'Guatemala', `abbreviation` = 'GTM', `iso2` = 'GT', `isonum` = 320 WHERE `countries_id` = 91;
        UPDATE `global_lu_countries` SET `country` = 'Guinea', `abbreviation` = 'GIN', `iso2` = 'GN', `isonum` = 324 WHERE `countries_id` = 92;
        UPDATE `global_lu_countries` SET `country` = 'Guinea-Bissau', `abbreviation` = 'GNB', `iso2` = 'GW', `isonum` = 624 WHERE `countries_id` = 93;
        UPDATE `global_lu_countries` SET `country` = 'Guyana', `abbreviation` = 'GUY', `iso2` = 'GY', `isonum` = 328 WHERE `countries_id` = 94;
        UPDATE `global_lu_countries` SET `country` = 'Haiti', `abbreviation` = 'HTI', `iso2` = 'HT', `isonum` = 332 WHERE `countries_id` = 95;
        UPDATE `global_lu_countries` SET `country` = 'Heard and McDonald Islands', `abbreviation` = 'HMD', `iso2` = 'HM', `isonum` = 334 WHERE `countries_id` = 96;
        UPDATE `global_lu_countries` SET `country` = 'Honduras', `abbreviation` = 'HND', `iso2` = 'HN', `isonum` = 340 WHERE `countries_id` = 97;
        UPDATE `global_lu_countries` SET `country` = 'Hong Kong', `abbreviation` = 'HKG', `iso2` = 'HK', `isonum` = 344 WHERE `countries_id` = 98;
        UPDATE `global_lu_countries` SET `country` = 'Hungary', `abbreviation` = 'HUN', `iso2` = 'HU', `isonum` = 348 WHERE `countries_id` = 99;
        UPDATE `global_lu_countries` SET `country` = 'Iceland', `abbreviation` = 'ISL', `iso2` = 'IS', `isonum` = 352 WHERE `countries_id` = 100;
        UPDATE `global_lu_countries` SET `country` = 'India', `abbreviation` = 'IND', `iso2` = 'IN', `isonum` = 356 WHERE `countries_id` = 101;
        UPDATE `global_lu_countries` SET `country` = 'Indonesia', `abbreviation` = 'IDN', `iso2` = 'ID', `isonum` = 360 WHERE `countries_id` = 102;
        UPDATE `global_lu_countries` SET `country` = 'Iran', `abbreviation` = 'IRN', `iso2` = 'IR', `isonum` = 364 WHERE `countries_id` = 103;
        UPDATE `global_lu_countries` SET `country` = 'Iraq', `abbreviation` = 'IRQ', `iso2` = 'IQ', `isonum` = 368 WHERE `countries_id` = 104;
        UPDATE `global_lu_countries` SET `country` = 'Ireland', `abbreviation` = 'IRL', `iso2` = 'IE', `isonum` = 372 WHERE `countries_id` = 105;
        UPDATE `global_lu_countries` SET `country` = 'Israel', `abbreviation` = 'ISR', `iso2` = 'IL', `isonum` = 376 WHERE `countries_id` = 106;
        UPDATE `global_lu_countries` SET `country` = 'Italy', `abbreviation` = 'ITA', `iso2` = 'IT', `isonum` = 380 WHERE `countries_id` = 107;
        UPDATE `global_lu_countries` SET `country` = 'Jamaica', `abbreviation` = 'JAM', `iso2` = 'JM', `isonum` = 388 WHERE `countries_id` = 108;
        UPDATE `global_lu_countries` SET `country` = 'Japan', `abbreviation` = 'JPN', `iso2` = 'JP', `isonum` = 392 WHERE `countries_id` = 109;
        UPDATE `global_lu_countries` SET `country` = 'Jordan', `abbreviation` = 'JOR', `iso2` = 'JO', `isonum` = 400 WHERE `countries_id` = 110;
        UPDATE `global_lu_countries` SET `country` = 'Kazakhstan', `abbreviation` = 'KAZ', `iso2` = 'KZ', `isonum` = 398 WHERE `countries_id` = 111;
        UPDATE `global_lu_countries` SET `country` = 'Kenya', `abbreviation` = 'KEN', `iso2` = 'KE', `isonum` = 404 WHERE `countries_id` = 112;
        UPDATE `global_lu_countries` SET `country` = 'Kiribati', `abbreviation` = 'KIR', `iso2` = 'KI', `isonum` = 296 WHERE `countries_id` = 113;
        UPDATE `global_lu_countries` SET `country` = 'Korea (north)', `abbreviation` = 'PRK', `iso2` = 'KP', `isonum` = 408 WHERE `countries_id` = 114;
        UPDATE `global_lu_countries` SET `country` = 'Korea (south)', `abbreviation` = 'KOR', `iso2` = 'KR', `isonum` = 410 WHERE `countries_id` = 115;
        UPDATE `global_lu_countries` SET `country` = 'Kuwait', `abbreviation` = 'KWT', `iso2` = 'KW', `isonum` = 414 WHERE `countries_id` = 116;
        UPDATE `global_lu_countries` SET `country` = 'Kyrgyzstan', `abbreviation` = 'KGZ', `iso2` = 'KG', `isonum` = 417 WHERE `countries_id` = 117;
        UPDATE `global_lu_countries` SET `country` = 'Lao People\'s Democratic Republic', `abbreviation` = 'LAO', `iso2` = 'LA', `isonum` = 418 WHERE `countries_id` = 118;
        UPDATE `global_lu_countries` SET `country` = 'Latvia', `abbreviation` = 'LVA', `iso2` = 'LV', `isonum` = 428 WHERE `countries_id` = 119;
        UPDATE `global_lu_countries` SET `country` = 'Lebanon', `abbreviation` = 'LBN', `iso2` = 'LB', `isonum` = 422 WHERE `countries_id` = 120;
        UPDATE `global_lu_countries` SET `country` = 'Lesotho', `abbreviation` = 'LSO', `iso2` = 'LS', `isonum` = 426 WHERE `countries_id` = 121;
        UPDATE `global_lu_countries` SET `country` = 'Liberia', `abbreviation` = 'LBR', `iso2` = 'LR', `isonum` = 430 WHERE `countries_id` = 122;
        UPDATE `global_lu_countries` SET `country` = 'Libyan Arab Jamahiriya', `abbreviation` = 'LBY', `iso2` = 'LY', `isonum` = 434 WHERE `countries_id` = 123;
        UPDATE `global_lu_countries` SET `country` = 'Liechtenstein', `abbreviation` = 'LIE', `iso2` = 'LI', `isonum` = 438 WHERE `countries_id` = 124;
        UPDATE `global_lu_countries` SET `country` = 'Lithuania', `abbreviation` = 'LTU', `iso2` = 'LT', `isonum` = 440 WHERE `countries_id` = 125;
        UPDATE `global_lu_countries` SET `country` = 'Luxembourg', `abbreviation` = 'LUX', `iso2` = 'LU', `isonum` = 442 WHERE `countries_id` = 126;
        UPDATE `global_lu_countries` SET `country` = 'Macao', `abbreviation` = 'MAC', `iso2` = 'MO', `isonum` = 446 WHERE `countries_id` = 127;
        UPDATE `global_lu_countries` SET `country` = 'Macedonia', `abbreviation` = 'MKD', `iso2` = 'MK', `isonum` = 807 WHERE `countries_id` = 128;
        UPDATE `global_lu_countries` SET `country` = 'Madagascar', `abbreviation` = 'MDG', `iso2` = 'MG', `isonum` = 450 WHERE `countries_id` = 129;
        UPDATE `global_lu_countries` SET `country` = 'Malawi', `abbreviation` = 'MWI', `iso2` = 'MW', `isonum` = 454 WHERE `countries_id` = 130;
        UPDATE `global_lu_countries` SET `country` = 'Malaysia', `abbreviation` = 'MYS', `iso2` = 'MY', `isonum` = 458 WHERE `countries_id` = 131;
        UPDATE `global_lu_countries` SET `country` = 'Maldives', `abbreviation` = 'MDV', `iso2` = 'MV', `isonum` = 462 WHERE `countries_id` = 132;
        UPDATE `global_lu_countries` SET `country` = 'Mali', `abbreviation` = 'MLI', `iso2` = 'ML', `isonum` = 466 WHERE `countries_id` = 133;
        UPDATE `global_lu_countries` SET `country` = 'Malta', `abbreviation` = 'MLT', `iso2` = 'MT', `isonum` = 470 WHERE `countries_id` = 134;
        UPDATE `global_lu_countries` SET `country` = 'Marshall Islands', `abbreviation` = 'MHL', `iso2` = 'MH', `isonum` = 584 WHERE `countries_id` = 135;
        UPDATE `global_lu_countries` SET `country` = 'Martinique', `abbreviation` = 'MTQ', `iso2` = 'MQ', `isonum` = 474 WHERE `countries_id` = 136;
        UPDATE `global_lu_countries` SET `country` = 'Mauritania', `abbreviation` = 'MRT', `iso2` = 'MR', `isonum` = 478 WHERE `countries_id` = 137;
        UPDATE `global_lu_countries` SET `country` = 'Mauritius', `abbreviation` = 'MUS', `iso2` = 'MU', `isonum` = 480 WHERE `countries_id` = 138;
        UPDATE `global_lu_countries` SET `country` = 'Mayotte', `abbreviation` = 'MYT', `iso2` = 'YT', `isonum` = 175 WHERE `countries_id` = 139;
        UPDATE `global_lu_countries` SET `country` = 'Mexico', `abbreviation` = 'MEX', `iso2` = 'MX', `isonum` = 484 WHERE `countries_id` = 140;
        UPDATE `global_lu_countries` SET `country` = 'Micronesia', `abbreviation` = 'FSM', `iso2` = 'FM', `isonum` = 583 WHERE `countries_id` = 141;
        UPDATE `global_lu_countries` SET `country` = 'Moldova', `abbreviation` = 'MDA', `iso2` = 'MD', `isonum` = 498 WHERE `countries_id` = 142;
        UPDATE `global_lu_countries` SET `country` = 'Monaco', `abbreviation` = 'MCO', `iso2` = 'MC', `isonum` = 492 WHERE `countries_id` = 143;
        UPDATE `global_lu_countries` SET `country` = 'Mongolia', `abbreviation` = 'MNG', `iso2` = 'MN', `isonum` = 496 WHERE `countries_id` = 144;
        UPDATE `global_lu_countries` SET `country` = 'Montserrat', `abbreviation` = 'MSR', `iso2` = 'MS', `isonum` = 500 WHERE `countries_id` = 145;
        UPDATE `global_lu_countries` SET `country` = 'Morocco', `abbreviation` = 'MAR', `iso2` = 'MA', `isonum` = 504 WHERE `countries_id` = 146;
        UPDATE `global_lu_countries` SET `country` = 'Mozambique', `abbreviation` = 'MOZ', `iso2` = 'MZ', `isonum` = 508 WHERE `countries_id` = 147;
        UPDATE `global_lu_countries` SET `country` = 'Myanmar', `abbreviation` = 'MMR', `iso2` = 'MM', `isonum` = 104 WHERE `countries_id` = 148;
        UPDATE `global_lu_countries` SET `country` = 'Namibia', `abbreviation` = 'NAM', `iso2` = 'NA', `isonum` = 516 WHERE `countries_id` = 149;
        UPDATE `global_lu_countries` SET `country` = 'Nauru', `abbreviation` = 'NRU', `iso2` = 'NR', `isonum` = 520 WHERE `countries_id` = 150;
        UPDATE `global_lu_countries` SET `country` = 'Nepal', `abbreviation` = 'NPL', `iso2` = 'NP', `isonum` = 524 WHERE `countries_id` = 151;
        UPDATE `global_lu_countries` SET `country` = 'Netherlands', `abbreviation` = 'NLD', `iso2` = 'NL', `isonum` = 528 WHERE `countries_id` = 152;
        UPDATE `global_lu_countries` SET `country` = 'Netherlands Antilles', `abbreviation` = 'CUW', `iso2` = 'CW', `isonum` = 531 WHERE `countries_id` = 153;
        UPDATE `global_lu_countries` SET `country` = 'New Caledonia', `abbreviation` = 'NCL', `iso2` = 'NC', `isonum` = 540 WHERE `countries_id` = 154;
        UPDATE `global_lu_countries` SET `country` = 'New Zealand', `abbreviation` = 'NZL', `iso2` = 'NZ', `isonum` = 554 WHERE `countries_id` = 155;
        UPDATE `global_lu_countries` SET `country` = 'Nicaragua', `abbreviation` = 'NIC', `iso2` = 'NI', `isonum` = 558 WHERE `countries_id` = 156;
        UPDATE `global_lu_countries` SET `country` = 'Niger', `abbreviation` = 'NER', `iso2` = 'NE', `isonum` = 562 WHERE `countries_id` = 157;
        UPDATE `global_lu_countries` SET `country` = 'Nigeria', `abbreviation` = 'NGA', `iso2` = 'NG', `isonum` = 566 WHERE `countries_id` = 158;
        UPDATE `global_lu_countries` SET `country` = 'Niue', `abbreviation` = 'NIU', `iso2` = 'NU', `isonum` = 570 WHERE `countries_id` = 159;
        UPDATE `global_lu_countries` SET `country` = 'Norfolk Island', `abbreviation` = 'NFK', `iso2` = 'NF', `isonum` = 574 WHERE `countries_id` = 160;
        UPDATE `global_lu_countries` SET `country` = 'Northern Mariana Islands', `abbreviation` = 'MNP', `iso2` = 'MP', `isonum` = 580 WHERE `countries_id` = 161;
        UPDATE `global_lu_countries` SET `country` = 'Norway', `abbreviation` = 'NOR', `iso2` = 'NO', `isonum` = 578 WHERE `countries_id` = 162;
        UPDATE `global_lu_countries` SET `country` = 'Oman', `abbreviation` = 'OMN', `iso2` = 'OM', `isonum` = 512 WHERE `countries_id` = 163;
        UPDATE `global_lu_countries` SET `country` = 'Pakistan', `abbreviation` = 'PAK', `iso2` = 'PK', `isonum` = 586 WHERE `countries_id` = 164;
        UPDATE `global_lu_countries` SET `country` = 'Palau', `abbreviation` = 'PLW', `iso2` = 'PW', `isonum` = 585 WHERE `countries_id` = 165;
        UPDATE `global_lu_countries` SET `country` = 'Palestinian Territories', `abbreviation` = 'PSE', `iso2` = 'PS', `isonum` = 275 WHERE `countries_id` = 166;
        UPDATE `global_lu_countries` SET `country` = 'Panama', `abbreviation` = 'PAN', `iso2` = 'PA', `isonum` = 591 WHERE `countries_id` = 167;
        UPDATE `global_lu_countries` SET `country` = 'Papua New Guinea', `abbreviation` = 'PNG', `iso2` = 'PG', `isonum` = 598 WHERE `countries_id` = 168;
        UPDATE `global_lu_countries` SET `country` = 'Paraguay', `abbreviation` = 'PRY', `iso2` = 'PY', `isonum` = 600 WHERE `countries_id` = 169;
        UPDATE `global_lu_countries` SET `country` = 'Peru', `abbreviation` = 'PER', `iso2` = 'PE', `isonum` = 604 WHERE `countries_id` = 170;
        UPDATE `global_lu_countries` SET `country` = 'Philippines', `abbreviation` = 'PHL', `iso2` = 'PH', `isonum` = 608 WHERE `countries_id` = 171;
        UPDATE `global_lu_countries` SET `country` = 'Pitcairn', `abbreviation` = 'PCN', `iso2` = 'PN', `isonum` = 612 WHERE `countries_id` = 172;
        UPDATE `global_lu_countries` SET `country` = 'Poland', `abbreviation` = 'POL', `iso2` = 'PL', `isonum` = 616 WHERE `countries_id` = 173;
        UPDATE `global_lu_countries` SET `country` = 'Portugal', `abbreviation` = 'PRT', `iso2` = 'PT', `isonum` = 620 WHERE `countries_id` = 174;
        UPDATE `global_lu_countries` SET `country` = 'Puerto Rico', `abbreviation` = 'PRI', `iso2` = 'PR', `isonum` = 630 WHERE `countries_id` = 175;
        UPDATE `global_lu_countries` SET `country` = 'Qatar', `abbreviation` = 'QAT', `iso2` = 'QA', `isonum` = 634 WHERE `countries_id` = 176;
        UPDATE `global_lu_countries` SET `country` = 'Reunion', `abbreviation` = 'REU', `iso2` = 'RE', `isonum` = 638 WHERE `countries_id` = 177;
        UPDATE `global_lu_countries` SET `country` = 'Romania', `abbreviation` = 'ROU', `iso2` = 'RO', `isonum` = 642 WHERE `countries_id` = 178;
        UPDATE `global_lu_countries` SET `country` = 'Russian Federation', `abbreviation` = 'RUS', `iso2` = 'RU', `isonum` = 643 WHERE `countries_id` = 179;
        UPDATE `global_lu_countries` SET `country` = 'Rwanda', `abbreviation` = 'RWA', `iso2` = 'RW', `isonum` = 646 WHERE `countries_id` = 180;
        UPDATE `global_lu_countries` SET `country` = 'Saint Helena', `abbreviation` = 'SHN', `iso2` = 'SH', `isonum` = 654 WHERE `countries_id` = 181;
        UPDATE `global_lu_countries` SET `country` = 'Saint Kitts and Nevis', `abbreviation` = 'KNA', `iso2` = 'KN', `isonum` = 659 WHERE `countries_id` = 182;
        UPDATE `global_lu_countries` SET `country` = 'Saint Lucia', `abbreviation` = 'LCA', `iso2` = 'LC', `isonum` = 662 WHERE `countries_id` = 183;
        UPDATE `global_lu_countries` SET `country` = 'Saint Pierre and Miquelon', `abbreviation` = 'SPM', `iso2` = 'PM', `isonum` = 666 WHERE `countries_id` = 184;
        UPDATE `global_lu_countries` SET `country` = 'Saint Vincent and the Grenadines', `abbreviation` = 'VCT', `iso2` = 'VC', `isonum` = 670 WHERE `countries_id` = 185;
        UPDATE `global_lu_countries` SET `country` = 'Samoa', `abbreviation` = 'WSM', `iso2` = 'WS', `isonum` = 882 WHERE `countries_id` = 186;
        UPDATE `global_lu_countries` SET `country` = 'San Marino', `abbreviation` = 'SMR', `iso2` = 'SM', `isonum` = 674 WHERE `countries_id` = 187;
        UPDATE `global_lu_countries` SET `country` = 'Sao Tome and Principe', `abbreviation` = 'STP', `iso2` = 'ST', `isonum` = 678 WHERE `countries_id` = 188;
        UPDATE `global_lu_countries` SET `country` = 'Saudi Arabia', `abbreviation` = 'SAU', `iso2` = 'SA', `isonum` = 682 WHERE `countries_id` = 189;
        UPDATE `global_lu_countries` SET `country` = 'Senegal', `abbreviation` = 'SEN', `iso2` = 'SN', `isonum` = 686 WHERE `countries_id` = 190;
        UPDATE `global_lu_countries` SET `country` = 'Serbia and Montenegro', `abbreviation` = 'SRB', `iso2` = 'RS', `isonum` = 688 WHERE `countries_id` = 191;
        UPDATE `global_lu_countries` SET `country` = 'Seychelles', `abbreviation` = 'SYC', `iso2` = 'SC', `isonum` = 690 WHERE `countries_id` = 192;
        UPDATE `global_lu_countries` SET `country` = 'Sierra Leone', `abbreviation` = 'SLE', `iso2` = 'SL', `isonum` = 694 WHERE `countries_id` = 193;
        UPDATE `global_lu_countries` SET `country` = 'Singapore', `abbreviation` = 'SGP', `iso2` = 'SG', `isonum` = 702 WHERE `countries_id` = 194;
        UPDATE `global_lu_countries` SET `country` = 'Slovakia', `abbreviation` = 'SVK', `iso2` = 'SK', `isonum` = 703 WHERE `countries_id` = 195;
        UPDATE `global_lu_countries` SET `country` = 'Slovenia', `abbreviation` = 'SVN', `iso2` = 'SI', `isonum` = 705 WHERE `countries_id` = 196;
        UPDATE `global_lu_countries` SET `country` = 'Solomon Islands', `abbreviation` = 'SLB', `iso2` = 'SB', `isonum` = 90 WHERE `countries_id` = 197;
        UPDATE `global_lu_countries` SET `country` = 'Somalia', `abbreviation` = 'SOM', `iso2` = 'SO', `isonum` = 706 WHERE `countries_id` = 198;
        UPDATE `global_lu_countries` SET `country` = 'South Africa', `abbreviation` = 'ZAF', `iso2` = 'ZA', `isonum` = 710 WHERE `countries_id` = 199;
        UPDATE `global_lu_countries` SET `country` = 'South Georgia and the South Sandwich Islands', `abbreviation` = 'SGS', `iso2` = 'GS', `isonum` = 239 WHERE `countries_id` = 200;
        UPDATE `global_lu_countries` SET `country` = 'Spain', `abbreviation` = 'ESP', `iso2` = 'ES', `isonum` = 724 WHERE `countries_id` = 201;
        UPDATE `global_lu_countries` SET `country` = 'Sri Lanka', `abbreviation` = 'LKA', `iso2` = 'LK', `isonum` = 144 WHERE `countries_id` = 202;
        UPDATE `global_lu_countries` SET `country` = 'Sudan', `abbreviation` = 'SDN', `iso2` = 'SD', `isonum` = 729 WHERE `countries_id` = 203;
        UPDATE `global_lu_countries` SET `country` = 'Suriname', `abbreviation` = 'SUR', `iso2` = 'SR', `isonum` = 740 WHERE `countries_id` = 204;
        UPDATE `global_lu_countries` SET `country` = 'Svalbard and Jan Mayen Islands', `abbreviation` = 'SJM', `iso2` = 'SJ', `isonum` = 744 WHERE `countries_id` = 205;
        UPDATE `global_lu_countries` SET `country` = 'Swaziland', `abbreviation` = 'SWZ', `iso2` = 'SZ', `isonum` = 748 WHERE `countries_id` = 206;
        UPDATE `global_lu_countries` SET `country` = 'Sweden', `abbreviation` = 'SWE', `iso2` = 'SE', `isonum` = 752 WHERE `countries_id` = 207;
        UPDATE `global_lu_countries` SET `country` = 'Switzerland', `abbreviation` = 'CHE', `iso2` = 'CH', `isonum` = 756 WHERE `countries_id` = 208;
        UPDATE `global_lu_countries` SET `country` = 'Syria', `abbreviation` = 'SYR', `iso2` = 'SY', `isonum` = 760 WHERE `countries_id` = 209;
        UPDATE `global_lu_countries` SET `country` = 'Taiwan', `abbreviation` = 'TWN', `iso2` = 'TW', `isonum` = 158 WHERE `countries_id` = 210;
        UPDATE `global_lu_countries` SET `country` = 'Tajikistan', `abbreviation` = 'TJK', `iso2` = 'TJ', `isonum` = 762 WHERE `countries_id` = 211;
        UPDATE `global_lu_countries` SET `country` = 'Tanzania', `abbreviation` = 'TZA', `iso2` = 'TZ', `isonum` = 834 WHERE `countries_id` = 212;
        UPDATE `global_lu_countries` SET `country` = 'Thailand', `abbreviation` = 'THA', `iso2` = 'TH', `isonum` = 764 WHERE `countries_id` = 213;
        UPDATE `global_lu_countries` SET `country` = 'Togo', `abbreviation` = 'TGO', `iso2` = 'TG', `isonum` = 768 WHERE `countries_id` = 214;
        UPDATE `global_lu_countries` SET `country` = 'Tokelau', `abbreviation` = 'TKL', `iso2` = 'TK', `isonum` = 772 WHERE `countries_id` = 215;
        UPDATE `global_lu_countries` SET `country` = 'Tonga', `abbreviation` = 'TON', `iso2` = 'TO', `isonum` = 776 WHERE `countries_id` = 216;
        UPDATE `global_lu_countries` SET `country` = 'Trinidad and Tobago', `abbreviation` = 'TTO', `iso2` = 'TT', `isonum` = 780 WHERE `countries_id` = 217;
        UPDATE `global_lu_countries` SET `country` = 'Tunisia', `abbreviation` = 'TUN', `iso2` = 'TN', `isonum` = 788 WHERE `countries_id` = 218;
        UPDATE `global_lu_countries` SET `country` = 'Turkey', `abbreviation` = 'TUR', `iso2` = 'TR', `isonum` = 792 WHERE `countries_id` = 219;
        UPDATE `global_lu_countries` SET `country` = 'Turkmenistan', `abbreviation` = 'TKM', `iso2` = 'TM', `isonum` = 795 WHERE `countries_id` = 220;
        UPDATE `global_lu_countries` SET `country` = 'Turks and Caicos Islands', `abbreviation` = 'TCA', `iso2` = 'TC', `isonum` = 796 WHERE `countries_id` = 221;
        UPDATE `global_lu_countries` SET `country` = 'Tuvalu', `abbreviation` = 'TUV', `iso2` = 'TV', `isonum` = 798 WHERE `countries_id` = 222;
        UPDATE `global_lu_countries` SET `country` = 'Uganda', `abbreviation` = 'UGA', `iso2` = 'UG', `isonum` = 800 WHERE `countries_id` = 223;
        UPDATE `global_lu_countries` SET `country` = 'Ukraine', `abbreviation` = 'UKR', `iso2` = 'UA', `isonum` = 804 WHERE `countries_id` = 224;
        UPDATE `global_lu_countries` SET `country` = 'United Arab Emirates', `abbreviation` = 'ARE', `iso2` = 'AE', `isonum` = 784 WHERE `countries_id` = 225;
        UPDATE `global_lu_countries` SET `country` = 'United Kingdom', `abbreviation` = 'GBR', `iso2` = 'GB', `isonum` = 826 WHERE `countries_id` = 226;
        UPDATE `global_lu_countries` SET `country` = 'United States of America', `abbreviation` = 'USA', `iso2` = 'US', `isonum` = 840 WHERE `countries_id` = 227;
        UPDATE `global_lu_countries` SET `country` = 'Uruguay', `abbreviation` = 'URY', `iso2` = 'UY', `isonum` = 858 WHERE `countries_id` = 228;
        UPDATE `global_lu_countries` SET `country` = 'Uzbekistan', `abbreviation` = 'UZB', `iso2` = 'UZ', `isonum` = 860 WHERE `countries_id` = 229;
        UPDATE `global_lu_countries` SET `country` = 'Vanuatu', `abbreviation` = 'VUT', `iso2` = 'VU', `isonum` = 548 WHERE `countries_id` = 230;
        UPDATE `global_lu_countries` SET `country` = 'Vatican City', `abbreviation` = 'VAT', `iso2` = 'VA', `isonum` = 336 WHERE `countries_id` = 231;
        UPDATE `global_lu_countries` SET `country` = 'Venezuela', `abbreviation` = 'VEN', `iso2` = 'VE', `isonum` = 862 WHERE `countries_id` = 232;
        UPDATE `global_lu_countries` SET `country` = 'Vietnam', `abbreviation` = 'VNM', `iso2` = 'VN', `isonum` = 704 WHERE `countries_id` = 233;
        UPDATE `global_lu_countries` SET `country` = 'Virgin Islands (British)', `abbreviation` = 'VGB', `iso2` = 'VG', `isonum` = 92 WHERE `countries_id` = 234;
        UPDATE `global_lu_countries` SET `country` = 'Virgin Islands (US)', `abbreviation` = 'VIR', `iso2` = 'VI', `isonum` = 850 WHERE `countries_id` = 235;
        UPDATE `global_lu_countries` SET `country` = 'Wallis and Futuna Islands', `abbreviation` = 'WLF', `iso2` = 'WF', `isonum` = 876 WHERE `countries_id` = 236;
        UPDATE `global_lu_countries` SET `country` = 'Western Sahara', `abbreviation` = 'ESH', `iso2` = 'EH', `isonum` = 732 WHERE `countries_id` = 237;
        UPDATE `global_lu_countries` SET `country` = 'Yemen', `abbreviation` = 'YEM', `iso2` = 'YE', `isonum` = 887 WHERE `countries_id` = 238;
        UPDATE `global_lu_countries` SET `country` = 'Congo, Democratic Republic of the', `abbreviation` = 'COD', `iso2` = 'CD', `isonum` = 180 WHERE `countries_id` = 239;
        UPDATE `global_lu_countries` SET `country` = 'Zambia', `abbreviation` = 'ZMB', `iso2` = 'ZM', `isonum` = 894 WHERE `countries_id` = 240;
        UPDATE `global_lu_countries` SET `country` = 'Zimbabwe', `abbreviation` = 'ZWE', `iso2` = 'ZW', `isonum` = 716 WHERE `countries_id` = 241;
        
        ALTER TABLE `global_lu_countries` ADD INDEX `abbr_idx` (`abbreviation`);
        ALTER TABLE `global_lu_countries` ADD INDEX `iso2_idx` (`iso2`);
        ALTER TABLE `global_lu_countries` ADD INDEX `isonum_idx` (`isonum`);
        
        ALTER TABLE `global_lu_countries` MODIFY `abbreviation` varchar(3) NOT NULL;
        ALTER TABLE `global_lu_countries` MODIFY `iso2` varchar(2) NOT NULL;
        ALTER TABLE `global_lu_countries` MODIFY `isonum` int(6) NOT NULL;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `global_lu_countries` DROP INDEX `abbr_idx`;
        ALTER TABLE `global_lu_countries` DROP INDEX `iso2_idx`;
        ALTER TABLE `global_lu_countries` DROP INDEX `isonum_idx`;
        
        ALTER TABLE `global_lu_countries` DROP COLUMN `abbreviation`;
        ALTER TABLE `global_lu_countries` DROP COLUMN `iso2`;
        ALTER TABLE `global_lu_countries` DROP COLUMN `isonum`;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Optional: PHP that verifies whether or not the changes outlined
     * in "up" are present in the active database.
     *
     * Return Values: -1 (not run) | 0 (changes not present or complete) | 1 (present)
     *
     * @return int
     */
    public function audit() {
        $migration = new Models_Migration();
        if ($migration->columnExists(DATABASE_NAME, "global_lu_countries", "abbreviation")) {
            if ($migration->columnExists(DATABASE_NAME, "global_lu_countries", "iso2")) {
                if ($migration->columnExists(DATABASE_NAME, "global_lu_countries", "isonum")) {
                    return 1;  
                }
            }
        }
        
        
        return 0;
    }
}
