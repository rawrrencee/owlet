<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            // Priority countries (sort_order 1-10)
            ['code' => 'SG', 'code_3' => 'SGP', 'name' => 'Singapore', 'nationality_name' => 'Singaporean', 'phone_code' => '+65', 'sort_order' => 1],
            ['code' => 'MY', 'code_3' => 'MYS', 'name' => 'Malaysia', 'nationality_name' => 'Malaysian', 'phone_code' => '+60', 'sort_order' => 2],
            ['code' => 'CN', 'code_3' => 'CHN', 'name' => 'China', 'nationality_name' => 'Chinese', 'phone_code' => '+86', 'sort_order' => 3],
            ['code' => 'IN', 'code_3' => 'IND', 'name' => 'India', 'nationality_name' => 'Indian', 'phone_code' => '+91', 'sort_order' => 4],
            ['code' => 'ID', 'code_3' => 'IDN', 'name' => 'Indonesia', 'nationality_name' => 'Indonesian', 'phone_code' => '+62', 'sort_order' => 5],
            ['code' => 'PH', 'code_3' => 'PHL', 'name' => 'Philippines', 'nationality_name' => 'Filipino', 'phone_code' => '+63', 'sort_order' => 6],
            ['code' => 'TH', 'code_3' => 'THA', 'name' => 'Thailand', 'nationality_name' => 'Thai', 'phone_code' => '+66', 'sort_order' => 7],
            ['code' => 'VN', 'code_3' => 'VNM', 'name' => 'Vietnam', 'nationality_name' => 'Vietnamese', 'phone_code' => '+84', 'sort_order' => 8],
            ['code' => 'MM', 'code_3' => 'MMR', 'name' => 'Myanmar', 'nationality_name' => 'Burmese', 'phone_code' => '+95', 'sort_order' => 9],
            ['code' => 'BD', 'code_3' => 'BGD', 'name' => 'Bangladesh', 'nationality_name' => 'Bangladeshi', 'phone_code' => '+880', 'sort_order' => 10],

            // Rest of Asia
            ['code' => 'JP', 'code_3' => 'JPN', 'name' => 'Japan', 'nationality_name' => 'Japanese', 'phone_code' => '+81', 'sort_order' => 100],
            ['code' => 'KR', 'code_3' => 'KOR', 'name' => 'South Korea', 'nationality_name' => 'South Korean', 'phone_code' => '+82', 'sort_order' => 100],
            ['code' => 'KP', 'code_3' => 'PRK', 'name' => 'North Korea', 'nationality_name' => 'North Korean', 'phone_code' => '+850', 'sort_order' => 999],
            ['code' => 'TW', 'code_3' => 'TWN', 'name' => 'Taiwan', 'nationality_name' => 'Taiwanese', 'phone_code' => '+886', 'sort_order' => 100],
            ['code' => 'HK', 'code_3' => 'HKG', 'name' => 'Hong Kong', 'nationality_name' => 'Hong Konger', 'phone_code' => '+852', 'sort_order' => 100],
            ['code' => 'MO', 'code_3' => 'MAC', 'name' => 'Macau', 'nationality_name' => 'Macanese', 'phone_code' => '+853', 'sort_order' => 100],
            ['code' => 'MN', 'code_3' => 'MNG', 'name' => 'Mongolia', 'nationality_name' => 'Mongolian', 'phone_code' => '+976', 'sort_order' => 999],
            ['code' => 'LA', 'code_3' => 'LAO', 'name' => 'Laos', 'nationality_name' => 'Laotian', 'phone_code' => '+856', 'sort_order' => 999],
            ['code' => 'KH', 'code_3' => 'KHM', 'name' => 'Cambodia', 'nationality_name' => 'Cambodian', 'phone_code' => '+855', 'sort_order' => 100],
            ['code' => 'BN', 'code_3' => 'BRN', 'name' => 'Brunei', 'nationality_name' => 'Bruneian', 'phone_code' => '+673', 'sort_order' => 100],
            ['code' => 'TL', 'code_3' => 'TLS', 'name' => 'Timor-Leste', 'nationality_name' => 'Timorese', 'phone_code' => '+670', 'sort_order' => 999],

            // South Asia
            ['code' => 'PK', 'code_3' => 'PAK', 'name' => 'Pakistan', 'nationality_name' => 'Pakistani', 'phone_code' => '+92', 'sort_order' => 100],
            ['code' => 'LK', 'code_3' => 'LKA', 'name' => 'Sri Lanka', 'nationality_name' => 'Sri Lankan', 'phone_code' => '+94', 'sort_order' => 100],
            ['code' => 'NP', 'code_3' => 'NPL', 'name' => 'Nepal', 'nationality_name' => 'Nepalese', 'phone_code' => '+977', 'sort_order' => 100],
            ['code' => 'BT', 'code_3' => 'BTN', 'name' => 'Bhutan', 'nationality_name' => 'Bhutanese', 'phone_code' => '+975', 'sort_order' => 999],
            ['code' => 'MV', 'code_3' => 'MDV', 'name' => 'Maldives', 'nationality_name' => 'Maldivian', 'phone_code' => '+960', 'sort_order' => 999],
            ['code' => 'AF', 'code_3' => 'AFG', 'name' => 'Afghanistan', 'nationality_name' => 'Afghan', 'phone_code' => '+93', 'sort_order' => 999],

            // Middle East
            ['code' => 'AE', 'code_3' => 'ARE', 'name' => 'United Arab Emirates', 'nationality_name' => 'Emirati', 'phone_code' => '+971', 'sort_order' => 100],
            ['code' => 'SA', 'code_3' => 'SAU', 'name' => 'Saudi Arabia', 'nationality_name' => 'Saudi', 'phone_code' => '+966', 'sort_order' => 100],
            ['code' => 'QA', 'code_3' => 'QAT', 'name' => 'Qatar', 'nationality_name' => 'Qatari', 'phone_code' => '+974', 'sort_order' => 100],
            ['code' => 'KW', 'code_3' => 'KWT', 'name' => 'Kuwait', 'nationality_name' => 'Kuwaiti', 'phone_code' => '+965', 'sort_order' => 100],
            ['code' => 'BH', 'code_3' => 'BHR', 'name' => 'Bahrain', 'nationality_name' => 'Bahraini', 'phone_code' => '+973', 'sort_order' => 100],
            ['code' => 'OM', 'code_3' => 'OMN', 'name' => 'Oman', 'nationality_name' => 'Omani', 'phone_code' => '+968', 'sort_order' => 100],
            ['code' => 'YE', 'code_3' => 'YEM', 'name' => 'Yemen', 'nationality_name' => 'Yemeni', 'phone_code' => '+967', 'sort_order' => 999],
            ['code' => 'IR', 'code_3' => 'IRN', 'name' => 'Iran', 'nationality_name' => 'Iranian', 'phone_code' => '+98', 'sort_order' => 999],
            ['code' => 'IQ', 'code_3' => 'IRQ', 'name' => 'Iraq', 'nationality_name' => 'Iraqi', 'phone_code' => '+964', 'sort_order' => 999],
            ['code' => 'SY', 'code_3' => 'SYR', 'name' => 'Syria', 'nationality_name' => 'Syrian', 'phone_code' => '+963', 'sort_order' => 999],
            ['code' => 'JO', 'code_3' => 'JOR', 'name' => 'Jordan', 'nationality_name' => 'Jordanian', 'phone_code' => '+962', 'sort_order' => 100],
            ['code' => 'LB', 'code_3' => 'LBN', 'name' => 'Lebanon', 'nationality_name' => 'Lebanese', 'phone_code' => '+961', 'sort_order' => 100],
            ['code' => 'IL', 'code_3' => 'ISR', 'name' => 'Israel', 'nationality_name' => 'Israeli', 'phone_code' => '+972', 'sort_order' => 100],
            ['code' => 'PS', 'code_3' => 'PSE', 'name' => 'Palestine', 'nationality_name' => 'Palestinian', 'phone_code' => '+970', 'sort_order' => 999],
            ['code' => 'TR', 'code_3' => 'TUR', 'name' => 'Turkey', 'nationality_name' => 'Turkish', 'phone_code' => '+90', 'sort_order' => 100],
            ['code' => 'CY', 'code_3' => 'CYP', 'name' => 'Cyprus', 'nationality_name' => 'Cypriot', 'phone_code' => '+357', 'sort_order' => 100],

            // Central Asia
            ['code' => 'KZ', 'code_3' => 'KAZ', 'name' => 'Kazakhstan', 'nationality_name' => 'Kazakhstani', 'phone_code' => '+7', 'sort_order' => 999],
            ['code' => 'UZ', 'code_3' => 'UZB', 'name' => 'Uzbekistan', 'nationality_name' => 'Uzbekistani', 'phone_code' => '+998', 'sort_order' => 999],
            ['code' => 'TM', 'code_3' => 'TKM', 'name' => 'Turkmenistan', 'nationality_name' => 'Turkmen', 'phone_code' => '+993', 'sort_order' => 999],
            ['code' => 'TJ', 'code_3' => 'TJK', 'name' => 'Tajikistan', 'nationality_name' => 'Tajikistani', 'phone_code' => '+992', 'sort_order' => 999],
            ['code' => 'KG', 'code_3' => 'KGZ', 'name' => 'Kyrgyzstan', 'nationality_name' => 'Kyrgyzstani', 'phone_code' => '+996', 'sort_order' => 999],
            ['code' => 'AZ', 'code_3' => 'AZE', 'name' => 'Azerbaijan', 'nationality_name' => 'Azerbaijani', 'phone_code' => '+994', 'sort_order' => 999],
            ['code' => 'AM', 'code_3' => 'ARM', 'name' => 'Armenia', 'nationality_name' => 'Armenian', 'phone_code' => '+374', 'sort_order' => 999],
            ['code' => 'GE', 'code_3' => 'GEO', 'name' => 'Georgia', 'nationality_name' => 'Georgian', 'phone_code' => '+995', 'sort_order' => 999],

            // Europe - Western
            ['code' => 'GB', 'code_3' => 'GBR', 'name' => 'United Kingdom', 'nationality_name' => 'British', 'phone_code' => '+44', 'sort_order' => 100],
            ['code' => 'FR', 'code_3' => 'FRA', 'name' => 'France', 'nationality_name' => 'French', 'phone_code' => '+33', 'sort_order' => 100],
            ['code' => 'DE', 'code_3' => 'DEU', 'name' => 'Germany', 'nationality_name' => 'German', 'phone_code' => '+49', 'sort_order' => 100],
            ['code' => 'IT', 'code_3' => 'ITA', 'name' => 'Italy', 'nationality_name' => 'Italian', 'phone_code' => '+39', 'sort_order' => 100],
            ['code' => 'ES', 'code_3' => 'ESP', 'name' => 'Spain', 'nationality_name' => 'Spanish', 'phone_code' => '+34', 'sort_order' => 100],
            ['code' => 'PT', 'code_3' => 'PRT', 'name' => 'Portugal', 'nationality_name' => 'Portuguese', 'phone_code' => '+351', 'sort_order' => 100],
            ['code' => 'NL', 'code_3' => 'NLD', 'name' => 'Netherlands', 'nationality_name' => 'Dutch', 'phone_code' => '+31', 'sort_order' => 100],
            ['code' => 'BE', 'code_3' => 'BEL', 'name' => 'Belgium', 'nationality_name' => 'Belgian', 'phone_code' => '+32', 'sort_order' => 100],
            ['code' => 'LU', 'code_3' => 'LUX', 'name' => 'Luxembourg', 'nationality_name' => 'Luxembourger', 'phone_code' => '+352', 'sort_order' => 999],
            ['code' => 'CH', 'code_3' => 'CHE', 'name' => 'Switzerland', 'nationality_name' => 'Swiss', 'phone_code' => '+41', 'sort_order' => 100],
            ['code' => 'AT', 'code_3' => 'AUT', 'name' => 'Austria', 'nationality_name' => 'Austrian', 'phone_code' => '+43', 'sort_order' => 100],
            ['code' => 'IE', 'code_3' => 'IRL', 'name' => 'Ireland', 'nationality_name' => 'Irish', 'phone_code' => '+353', 'sort_order' => 100],
            ['code' => 'MC', 'code_3' => 'MCO', 'name' => 'Monaco', 'nationality_name' => 'Monegasque', 'phone_code' => '+377', 'sort_order' => 999],
            ['code' => 'AD', 'code_3' => 'AND', 'name' => 'Andorra', 'nationality_name' => 'Andorran', 'phone_code' => '+376', 'sort_order' => 999],
            ['code' => 'LI', 'code_3' => 'LIE', 'name' => 'Liechtenstein', 'nationality_name' => 'Liechtensteiner', 'phone_code' => '+423', 'sort_order' => 999],
            ['code' => 'SM', 'code_3' => 'SMR', 'name' => 'San Marino', 'nationality_name' => 'Sammarinese', 'phone_code' => '+378', 'sort_order' => 999],
            ['code' => 'VA', 'code_3' => 'VAT', 'name' => 'Vatican City', 'nationality_name' => 'Vatican', 'phone_code' => '+39', 'sort_order' => 999],
            ['code' => 'MT', 'code_3' => 'MLT', 'name' => 'Malta', 'nationality_name' => 'Maltese', 'phone_code' => '+356', 'sort_order' => 999],

            // Europe - Northern
            ['code' => 'SE', 'code_3' => 'SWE', 'name' => 'Sweden', 'nationality_name' => 'Swedish', 'phone_code' => '+46', 'sort_order' => 100],
            ['code' => 'NO', 'code_3' => 'NOR', 'name' => 'Norway', 'nationality_name' => 'Norwegian', 'phone_code' => '+47', 'sort_order' => 100],
            ['code' => 'DK', 'code_3' => 'DNK', 'name' => 'Denmark', 'nationality_name' => 'Danish', 'phone_code' => '+45', 'sort_order' => 100],
            ['code' => 'FI', 'code_3' => 'FIN', 'name' => 'Finland', 'nationality_name' => 'Finnish', 'phone_code' => '+358', 'sort_order' => 100],
            ['code' => 'IS', 'code_3' => 'ISL', 'name' => 'Iceland', 'nationality_name' => 'Icelandic', 'phone_code' => '+354', 'sort_order' => 999],
            ['code' => 'EE', 'code_3' => 'EST', 'name' => 'Estonia', 'nationality_name' => 'Estonian', 'phone_code' => '+372', 'sort_order' => 999],
            ['code' => 'LV', 'code_3' => 'LVA', 'name' => 'Latvia', 'nationality_name' => 'Latvian', 'phone_code' => '+371', 'sort_order' => 999],
            ['code' => 'LT', 'code_3' => 'LTU', 'name' => 'Lithuania', 'nationality_name' => 'Lithuanian', 'phone_code' => '+370', 'sort_order' => 999],

            // Europe - Eastern
            ['code' => 'RU', 'code_3' => 'RUS', 'name' => 'Russia', 'nationality_name' => 'Russian', 'phone_code' => '+7', 'sort_order' => 100],
            ['code' => 'UA', 'code_3' => 'UKR', 'name' => 'Ukraine', 'nationality_name' => 'Ukrainian', 'phone_code' => '+380', 'sort_order' => 100],
            ['code' => 'PL', 'code_3' => 'POL', 'name' => 'Poland', 'nationality_name' => 'Polish', 'phone_code' => '+48', 'sort_order' => 100],
            ['code' => 'CZ', 'code_3' => 'CZE', 'name' => 'Czech Republic', 'nationality_name' => 'Czech', 'phone_code' => '+420', 'sort_order' => 100],
            ['code' => 'SK', 'code_3' => 'SVK', 'name' => 'Slovakia', 'nationality_name' => 'Slovak', 'phone_code' => '+421', 'sort_order' => 999],
            ['code' => 'HU', 'code_3' => 'HUN', 'name' => 'Hungary', 'nationality_name' => 'Hungarian', 'phone_code' => '+36', 'sort_order' => 100],
            ['code' => 'RO', 'code_3' => 'ROU', 'name' => 'Romania', 'nationality_name' => 'Romanian', 'phone_code' => '+40', 'sort_order' => 100],
            ['code' => 'BG', 'code_3' => 'BGR', 'name' => 'Bulgaria', 'nationality_name' => 'Bulgarian', 'phone_code' => '+359', 'sort_order' => 999],
            ['code' => 'MD', 'code_3' => 'MDA', 'name' => 'Moldova', 'nationality_name' => 'Moldovan', 'phone_code' => '+373', 'sort_order' => 999],
            ['code' => 'BY', 'code_3' => 'BLR', 'name' => 'Belarus', 'nationality_name' => 'Belarusian', 'phone_code' => '+375', 'sort_order' => 999],

            // Europe - Balkans
            ['code' => 'GR', 'code_3' => 'GRC', 'name' => 'Greece', 'nationality_name' => 'Greek', 'phone_code' => '+30', 'sort_order' => 100],
            ['code' => 'HR', 'code_3' => 'HRV', 'name' => 'Croatia', 'nationality_name' => 'Croatian', 'phone_code' => '+385', 'sort_order' => 999],
            ['code' => 'SI', 'code_3' => 'SVN', 'name' => 'Slovenia', 'nationality_name' => 'Slovenian', 'phone_code' => '+386', 'sort_order' => 999],
            ['code' => 'BA', 'code_3' => 'BIH', 'name' => 'Bosnia and Herzegovina', 'nationality_name' => 'Bosnian', 'phone_code' => '+387', 'sort_order' => 999],
            ['code' => 'RS', 'code_3' => 'SRB', 'name' => 'Serbia', 'nationality_name' => 'Serbian', 'phone_code' => '+381', 'sort_order' => 999],
            ['code' => 'ME', 'code_3' => 'MNE', 'name' => 'Montenegro', 'nationality_name' => 'Montenegrin', 'phone_code' => '+382', 'sort_order' => 999],
            ['code' => 'MK', 'code_3' => 'MKD', 'name' => 'North Macedonia', 'nationality_name' => 'Macedonian', 'phone_code' => '+389', 'sort_order' => 999],
            ['code' => 'AL', 'code_3' => 'ALB', 'name' => 'Albania', 'nationality_name' => 'Albanian', 'phone_code' => '+355', 'sort_order' => 999],
            ['code' => 'XK', 'code_3' => 'XKX', 'name' => 'Kosovo', 'nationality_name' => 'Kosovar', 'phone_code' => '+383', 'sort_order' => 999],

            // North America
            ['code' => 'US', 'code_3' => 'USA', 'name' => 'United States', 'nationality_name' => 'American', 'phone_code' => '+1', 'sort_order' => 100],
            ['code' => 'CA', 'code_3' => 'CAN', 'name' => 'Canada', 'nationality_name' => 'Canadian', 'phone_code' => '+1', 'sort_order' => 100],
            ['code' => 'MX', 'code_3' => 'MEX', 'name' => 'Mexico', 'nationality_name' => 'Mexican', 'phone_code' => '+52', 'sort_order' => 100],

            // Central America
            ['code' => 'GT', 'code_3' => 'GTM', 'name' => 'Guatemala', 'nationality_name' => 'Guatemalan', 'phone_code' => '+502', 'sort_order' => 999],
            ['code' => 'BZ', 'code_3' => 'BLZ', 'name' => 'Belize', 'nationality_name' => 'Belizean', 'phone_code' => '+501', 'sort_order' => 999],
            ['code' => 'SV', 'code_3' => 'SLV', 'name' => 'El Salvador', 'nationality_name' => 'Salvadoran', 'phone_code' => '+503', 'sort_order' => 999],
            ['code' => 'HN', 'code_3' => 'HND', 'name' => 'Honduras', 'nationality_name' => 'Honduran', 'phone_code' => '+504', 'sort_order' => 999],
            ['code' => 'NI', 'code_3' => 'NIC', 'name' => 'Nicaragua', 'nationality_name' => 'Nicaraguan', 'phone_code' => '+505', 'sort_order' => 999],
            ['code' => 'CR', 'code_3' => 'CRI', 'name' => 'Costa Rica', 'nationality_name' => 'Costa Rican', 'phone_code' => '+506', 'sort_order' => 999],
            ['code' => 'PA', 'code_3' => 'PAN', 'name' => 'Panama', 'nationality_name' => 'Panamanian', 'phone_code' => '+507', 'sort_order' => 999],

            // Caribbean
            ['code' => 'CU', 'code_3' => 'CUB', 'name' => 'Cuba', 'nationality_name' => 'Cuban', 'phone_code' => '+53', 'sort_order' => 999],
            ['code' => 'JM', 'code_3' => 'JAM', 'name' => 'Jamaica', 'nationality_name' => 'Jamaican', 'phone_code' => '+1876', 'sort_order' => 999],
            ['code' => 'HT', 'code_3' => 'HTI', 'name' => 'Haiti', 'nationality_name' => 'Haitian', 'phone_code' => '+509', 'sort_order' => 999],
            ['code' => 'DO', 'code_3' => 'DOM', 'name' => 'Dominican Republic', 'nationality_name' => 'Dominican', 'phone_code' => '+1809', 'sort_order' => 999],
            ['code' => 'PR', 'code_3' => 'PRI', 'name' => 'Puerto Rico', 'nationality_name' => 'Puerto Rican', 'phone_code' => '+1787', 'sort_order' => 999],
            ['code' => 'TT', 'code_3' => 'TTO', 'name' => 'Trinidad and Tobago', 'nationality_name' => 'Trinidadian', 'phone_code' => '+1868', 'sort_order' => 999],
            ['code' => 'BS', 'code_3' => 'BHS', 'name' => 'Bahamas', 'nationality_name' => 'Bahamian', 'phone_code' => '+1242', 'sort_order' => 999],
            ['code' => 'BB', 'code_3' => 'BRB', 'name' => 'Barbados', 'nationality_name' => 'Barbadian', 'phone_code' => '+1246', 'sort_order' => 999],
            ['code' => 'LC', 'code_3' => 'LCA', 'name' => 'Saint Lucia', 'nationality_name' => 'Saint Lucian', 'phone_code' => '+1758', 'sort_order' => 999],
            ['code' => 'VC', 'code_3' => 'VCT', 'name' => 'Saint Vincent and the Grenadines', 'nationality_name' => 'Vincentian', 'phone_code' => '+1784', 'sort_order' => 999],
            ['code' => 'GD', 'code_3' => 'GRD', 'name' => 'Grenada', 'nationality_name' => 'Grenadian', 'phone_code' => '+1473', 'sort_order' => 999],
            ['code' => 'AG', 'code_3' => 'ATG', 'name' => 'Antigua and Barbuda', 'nationality_name' => 'Antiguan', 'phone_code' => '+1268', 'sort_order' => 999],
            ['code' => 'DM', 'code_3' => 'DMA', 'name' => 'Dominica', 'nationality_name' => 'Dominican', 'phone_code' => '+1767', 'sort_order' => 999],
            ['code' => 'KN', 'code_3' => 'KNA', 'name' => 'Saint Kitts and Nevis', 'nationality_name' => 'Kittitian', 'phone_code' => '+1869', 'sort_order' => 999],

            // South America
            ['code' => 'BR', 'code_3' => 'BRA', 'name' => 'Brazil', 'nationality_name' => 'Brazilian', 'phone_code' => '+55', 'sort_order' => 100],
            ['code' => 'AR', 'code_3' => 'ARG', 'name' => 'Argentina', 'nationality_name' => 'Argentine', 'phone_code' => '+54', 'sort_order' => 100],
            ['code' => 'CL', 'code_3' => 'CHL', 'name' => 'Chile', 'nationality_name' => 'Chilean', 'phone_code' => '+56', 'sort_order' => 100],
            ['code' => 'CO', 'code_3' => 'COL', 'name' => 'Colombia', 'nationality_name' => 'Colombian', 'phone_code' => '+57', 'sort_order' => 100],
            ['code' => 'PE', 'code_3' => 'PER', 'name' => 'Peru', 'nationality_name' => 'Peruvian', 'phone_code' => '+51', 'sort_order' => 100],
            ['code' => 'VE', 'code_3' => 'VEN', 'name' => 'Venezuela', 'nationality_name' => 'Venezuelan', 'phone_code' => '+58', 'sort_order' => 999],
            ['code' => 'EC', 'code_3' => 'ECU', 'name' => 'Ecuador', 'nationality_name' => 'Ecuadorian', 'phone_code' => '+593', 'sort_order' => 999],
            ['code' => 'BO', 'code_3' => 'BOL', 'name' => 'Bolivia', 'nationality_name' => 'Bolivian', 'phone_code' => '+591', 'sort_order' => 999],
            ['code' => 'PY', 'code_3' => 'PRY', 'name' => 'Paraguay', 'nationality_name' => 'Paraguayan', 'phone_code' => '+595', 'sort_order' => 999],
            ['code' => 'UY', 'code_3' => 'URY', 'name' => 'Uruguay', 'nationality_name' => 'Uruguayan', 'phone_code' => '+598', 'sort_order' => 999],
            ['code' => 'GY', 'code_3' => 'GUY', 'name' => 'Guyana', 'nationality_name' => 'Guyanese', 'phone_code' => '+592', 'sort_order' => 999],
            ['code' => 'SR', 'code_3' => 'SUR', 'name' => 'Suriname', 'nationality_name' => 'Surinamese', 'phone_code' => '+597', 'sort_order' => 999],

            // Africa - North
            ['code' => 'EG', 'code_3' => 'EGY', 'name' => 'Egypt', 'nationality_name' => 'Egyptian', 'phone_code' => '+20', 'sort_order' => 100],
            ['code' => 'LY', 'code_3' => 'LBY', 'name' => 'Libya', 'nationality_name' => 'Libyan', 'phone_code' => '+218', 'sort_order' => 999],
            ['code' => 'TN', 'code_3' => 'TUN', 'name' => 'Tunisia', 'nationality_name' => 'Tunisian', 'phone_code' => '+216', 'sort_order' => 999],
            ['code' => 'DZ', 'code_3' => 'DZA', 'name' => 'Algeria', 'nationality_name' => 'Algerian', 'phone_code' => '+213', 'sort_order' => 999],
            ['code' => 'MA', 'code_3' => 'MAR', 'name' => 'Morocco', 'nationality_name' => 'Moroccan', 'phone_code' => '+212', 'sort_order' => 100],
            ['code' => 'SD', 'code_3' => 'SDN', 'name' => 'Sudan', 'nationality_name' => 'Sudanese', 'phone_code' => '+249', 'sort_order' => 999],
            ['code' => 'SS', 'code_3' => 'SSD', 'name' => 'South Sudan', 'nationality_name' => 'South Sudanese', 'phone_code' => '+211', 'sort_order' => 999],

            // Africa - West
            ['code' => 'NG', 'code_3' => 'NGA', 'name' => 'Nigeria', 'nationality_name' => 'Nigerian', 'phone_code' => '+234', 'sort_order' => 100],
            ['code' => 'GH', 'code_3' => 'GHA', 'name' => 'Ghana', 'nationality_name' => 'Ghanaian', 'phone_code' => '+233', 'sort_order' => 100],
            ['code' => 'CI', 'code_3' => 'CIV', 'name' => 'Ivory Coast', 'nationality_name' => 'Ivorian', 'phone_code' => '+225', 'sort_order' => 999],
            ['code' => 'SN', 'code_3' => 'SEN', 'name' => 'Senegal', 'nationality_name' => 'Senegalese', 'phone_code' => '+221', 'sort_order' => 999],
            ['code' => 'ML', 'code_3' => 'MLI', 'name' => 'Mali', 'nationality_name' => 'Malian', 'phone_code' => '+223', 'sort_order' => 999],
            ['code' => 'BF', 'code_3' => 'BFA', 'name' => 'Burkina Faso', 'nationality_name' => 'Burkinabe', 'phone_code' => '+226', 'sort_order' => 999],
            ['code' => 'NE', 'code_3' => 'NER', 'name' => 'Niger', 'nationality_name' => 'Nigerien', 'phone_code' => '+227', 'sort_order' => 999],
            ['code' => 'MR', 'code_3' => 'MRT', 'name' => 'Mauritania', 'nationality_name' => 'Mauritanian', 'phone_code' => '+222', 'sort_order' => 999],
            ['code' => 'GM', 'code_3' => 'GMB', 'name' => 'Gambia', 'nationality_name' => 'Gambian', 'phone_code' => '+220', 'sort_order' => 999],
            ['code' => 'GW', 'code_3' => 'GNB', 'name' => 'Guinea-Bissau', 'nationality_name' => 'Bissau-Guinean', 'phone_code' => '+245', 'sort_order' => 999],
            ['code' => 'GN', 'code_3' => 'GIN', 'name' => 'Guinea', 'nationality_name' => 'Guinean', 'phone_code' => '+224', 'sort_order' => 999],
            ['code' => 'SL', 'code_3' => 'SLE', 'name' => 'Sierra Leone', 'nationality_name' => 'Sierra Leonean', 'phone_code' => '+232', 'sort_order' => 999],
            ['code' => 'LR', 'code_3' => 'LBR', 'name' => 'Liberia', 'nationality_name' => 'Liberian', 'phone_code' => '+231', 'sort_order' => 999],
            ['code' => 'TG', 'code_3' => 'TGO', 'name' => 'Togo', 'nationality_name' => 'Togolese', 'phone_code' => '+228', 'sort_order' => 999],
            ['code' => 'BJ', 'code_3' => 'BEN', 'name' => 'Benin', 'nationality_name' => 'Beninese', 'phone_code' => '+229', 'sort_order' => 999],
            ['code' => 'CV', 'code_3' => 'CPV', 'name' => 'Cape Verde', 'nationality_name' => 'Cape Verdean', 'phone_code' => '+238', 'sort_order' => 999],

            // Africa - Central
            ['code' => 'CM', 'code_3' => 'CMR', 'name' => 'Cameroon', 'nationality_name' => 'Cameroonian', 'phone_code' => '+237', 'sort_order' => 999],
            ['code' => 'CD', 'code_3' => 'COD', 'name' => 'Democratic Republic of the Congo', 'nationality_name' => 'Congolese', 'phone_code' => '+243', 'sort_order' => 999],
            ['code' => 'CG', 'code_3' => 'COG', 'name' => 'Republic of the Congo', 'nationality_name' => 'Congolese', 'phone_code' => '+242', 'sort_order' => 999],
            ['code' => 'CF', 'code_3' => 'CAF', 'name' => 'Central African Republic', 'nationality_name' => 'Central African', 'phone_code' => '+236', 'sort_order' => 999],
            ['code' => 'TD', 'code_3' => 'TCD', 'name' => 'Chad', 'nationality_name' => 'Chadian', 'phone_code' => '+235', 'sort_order' => 999],
            ['code' => 'GA', 'code_3' => 'GAB', 'name' => 'Gabon', 'nationality_name' => 'Gabonese', 'phone_code' => '+241', 'sort_order' => 999],
            ['code' => 'GQ', 'code_3' => 'GNQ', 'name' => 'Equatorial Guinea', 'nationality_name' => 'Equatoguinean', 'phone_code' => '+240', 'sort_order' => 999],
            ['code' => 'ST', 'code_3' => 'STP', 'name' => 'Sao Tome and Principe', 'nationality_name' => 'Sao Tomean', 'phone_code' => '+239', 'sort_order' => 999],

            // Africa - East
            ['code' => 'KE', 'code_3' => 'KEN', 'name' => 'Kenya', 'nationality_name' => 'Kenyan', 'phone_code' => '+254', 'sort_order' => 100],
            ['code' => 'TZ', 'code_3' => 'TZA', 'name' => 'Tanzania', 'nationality_name' => 'Tanzanian', 'phone_code' => '+255', 'sort_order' => 999],
            ['code' => 'UG', 'code_3' => 'UGA', 'name' => 'Uganda', 'nationality_name' => 'Ugandan', 'phone_code' => '+256', 'sort_order' => 999],
            ['code' => 'RW', 'code_3' => 'RWA', 'name' => 'Rwanda', 'nationality_name' => 'Rwandan', 'phone_code' => '+250', 'sort_order' => 999],
            ['code' => 'BI', 'code_3' => 'BDI', 'name' => 'Burundi', 'nationality_name' => 'Burundian', 'phone_code' => '+257', 'sort_order' => 999],
            ['code' => 'ET', 'code_3' => 'ETH', 'name' => 'Ethiopia', 'nationality_name' => 'Ethiopian', 'phone_code' => '+251', 'sort_order' => 100],
            ['code' => 'ER', 'code_3' => 'ERI', 'name' => 'Eritrea', 'nationality_name' => 'Eritrean', 'phone_code' => '+291', 'sort_order' => 999],
            ['code' => 'DJ', 'code_3' => 'DJI', 'name' => 'Djibouti', 'nationality_name' => 'Djiboutian', 'phone_code' => '+253', 'sort_order' => 999],
            ['code' => 'SO', 'code_3' => 'SOM', 'name' => 'Somalia', 'nationality_name' => 'Somali', 'phone_code' => '+252', 'sort_order' => 999],
            ['code' => 'MG', 'code_3' => 'MDG', 'name' => 'Madagascar', 'nationality_name' => 'Malagasy', 'phone_code' => '+261', 'sort_order' => 999],
            ['code' => 'MU', 'code_3' => 'MUS', 'name' => 'Mauritius', 'nationality_name' => 'Mauritian', 'phone_code' => '+230', 'sort_order' => 999],
            ['code' => 'SC', 'code_3' => 'SYC', 'name' => 'Seychelles', 'nationality_name' => 'Seychellois', 'phone_code' => '+248', 'sort_order' => 999],
            ['code' => 'KM', 'code_3' => 'COM', 'name' => 'Comoros', 'nationality_name' => 'Comoran', 'phone_code' => '+269', 'sort_order' => 999],

            // Africa - Southern
            ['code' => 'ZA', 'code_3' => 'ZAF', 'name' => 'South Africa', 'nationality_name' => 'South African', 'phone_code' => '+27', 'sort_order' => 100],
            ['code' => 'ZW', 'code_3' => 'ZWE', 'name' => 'Zimbabwe', 'nationality_name' => 'Zimbabwean', 'phone_code' => '+263', 'sort_order' => 999],
            ['code' => 'ZM', 'code_3' => 'ZMB', 'name' => 'Zambia', 'nationality_name' => 'Zambian', 'phone_code' => '+260', 'sort_order' => 999],
            ['code' => 'MW', 'code_3' => 'MWI', 'name' => 'Malawi', 'nationality_name' => 'Malawian', 'phone_code' => '+265', 'sort_order' => 999],
            ['code' => 'MZ', 'code_3' => 'MOZ', 'name' => 'Mozambique', 'nationality_name' => 'Mozambican', 'phone_code' => '+258', 'sort_order' => 999],
            ['code' => 'BW', 'code_3' => 'BWA', 'name' => 'Botswana', 'nationality_name' => 'Motswana', 'phone_code' => '+267', 'sort_order' => 999],
            ['code' => 'NA', 'code_3' => 'NAM', 'name' => 'Namibia', 'nationality_name' => 'Namibian', 'phone_code' => '+264', 'sort_order' => 999],
            ['code' => 'AO', 'code_3' => 'AGO', 'name' => 'Angola', 'nationality_name' => 'Angolan', 'phone_code' => '+244', 'sort_order' => 999],
            ['code' => 'SZ', 'code_3' => 'SWZ', 'name' => 'Eswatini', 'nationality_name' => 'Swazi', 'phone_code' => '+268', 'sort_order' => 999],
            ['code' => 'LS', 'code_3' => 'LSO', 'name' => 'Lesotho', 'nationality_name' => 'Mosotho', 'phone_code' => '+266', 'sort_order' => 999],

            // Oceania
            ['code' => 'AU', 'code_3' => 'AUS', 'name' => 'Australia', 'nationality_name' => 'Australian', 'phone_code' => '+61', 'sort_order' => 100],
            ['code' => 'NZ', 'code_3' => 'NZL', 'name' => 'New Zealand', 'nationality_name' => 'New Zealander', 'phone_code' => '+64', 'sort_order' => 100],
            ['code' => 'PG', 'code_3' => 'PNG', 'name' => 'Papua New Guinea', 'nationality_name' => 'Papua New Guinean', 'phone_code' => '+675', 'sort_order' => 999],
            ['code' => 'FJ', 'code_3' => 'FJI', 'name' => 'Fiji', 'nationality_name' => 'Fijian', 'phone_code' => '+679', 'sort_order' => 999],
            ['code' => 'SB', 'code_3' => 'SLB', 'name' => 'Solomon Islands', 'nationality_name' => 'Solomon Islander', 'phone_code' => '+677', 'sort_order' => 999],
            ['code' => 'VU', 'code_3' => 'VUT', 'name' => 'Vanuatu', 'nationality_name' => 'Ni-Vanuatu', 'phone_code' => '+678', 'sort_order' => 999],
            ['code' => 'NC', 'code_3' => 'NCL', 'name' => 'New Caledonia', 'nationality_name' => 'New Caledonian', 'phone_code' => '+687', 'sort_order' => 999],
            ['code' => 'PF', 'code_3' => 'PYF', 'name' => 'French Polynesia', 'nationality_name' => 'French Polynesian', 'phone_code' => '+689', 'sort_order' => 999],
            ['code' => 'WS', 'code_3' => 'WSM', 'name' => 'Samoa', 'nationality_name' => 'Samoan', 'phone_code' => '+685', 'sort_order' => 999],
            ['code' => 'TO', 'code_3' => 'TON', 'name' => 'Tonga', 'nationality_name' => 'Tongan', 'phone_code' => '+676', 'sort_order' => 999],
            ['code' => 'KI', 'code_3' => 'KIR', 'name' => 'Kiribati', 'nationality_name' => 'I-Kiribati', 'phone_code' => '+686', 'sort_order' => 999],
            ['code' => 'FM', 'code_3' => 'FSM', 'name' => 'Micronesia', 'nationality_name' => 'Micronesian', 'phone_code' => '+691', 'sort_order' => 999],
            ['code' => 'MH', 'code_3' => 'MHL', 'name' => 'Marshall Islands', 'nationality_name' => 'Marshallese', 'phone_code' => '+692', 'sort_order' => 999],
            ['code' => 'PW', 'code_3' => 'PLW', 'name' => 'Palau', 'nationality_name' => 'Palauan', 'phone_code' => '+680', 'sort_order' => 999],
            ['code' => 'NR', 'code_3' => 'NRU', 'name' => 'Nauru', 'nationality_name' => 'Nauruan', 'phone_code' => '+674', 'sort_order' => 999],
            ['code' => 'TV', 'code_3' => 'TUV', 'name' => 'Tuvalu', 'nationality_name' => 'Tuvaluan', 'phone_code' => '+688', 'sort_order' => 999],
            ['code' => 'GU', 'code_3' => 'GUM', 'name' => 'Guam', 'nationality_name' => 'Guamanian', 'phone_code' => '+1671', 'sort_order' => 999],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}
