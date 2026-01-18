<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BilancoTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * Bilanço Excel template data
     * Hesap adları kullanıcı tarafından verilen tam liste
     */
    public function array(): array
    {
        return [
            // Başlık satırı boş bırakılacak (headings() ile eklenecek)
            
            // VARLIKLAR / DÖNEN VARLIKLAR
            ['DÖNEN VARLIKLAR', '', '', ''],
            ['  Nakit ve Nakit Benzerleri', '', '', ''],
            ['    Kasa', '', '', ''],
            ['    Alınan Çekler', '', '', ''],
            ['    Bankalar', '', '', ''],
            ['    Verilen Çekler ve Ödeme Emirleri (-)', '', '', ''],
            ['    Likit Fonlar, Altın vb.', '', '', ''],
            ['    Diğer Hazır Değerler', '', '', ''],
            ['  Finansal Yatırımlar', '', '', ''],
            ['    Gerçeğe Uygun Değer Farkları Kar/Zarara Yansıtılan Finansal Varlıklar', '', '', ''],
            ['    Satılmaya Hazır Finansal Varlıklar', '', '', ''],
            ['    Vadeye Kadar Elde Tutulacak Finansal Varlıklar (90-365 gün)', '', '', ''],
            ['    Aktif Piyasası Olmayan Finansal Varlıklar (Maliyetle Değerlenen)', '', '', ''],
            ['    Hisse Senetleri ve Diğer Menkul Kıymetler', '', '', ''],
            ['    Özel/Kamu Kesimi Tahvil, Senet ve Bonolar', '', '', ''],
            ['    Menkul Kıymet Değer Düşüklüğü Karşılığı (-)', '', '', ''],
            ['  Türev Araçlar', '', '', ''],
            ['  Teminata Verilen Finansal Varlıklar', '', '', ''],
            ['  Ticari Alacaklar', '', '', ''],
            ['    İlişkili Olmayan Taraflardan Ticari Alacaklar', '', '', ''],
            ['      Alıcılar', '', '', ''],
            ['      Alacak Senetleri', '', '', ''],
            ['      Alacak Reeskontu (-)', '', '', ''],
            ['      Şüpheli Ticari Alacaklar', '', '', ''],
            ['      Şüpheli Ticari Alacaklar Karşılığı (-)', '', '', ''],
            ['    İlişkili Taraflardan Ticari Alacaklar', '', '', ''],
            ['      Ortaklardan Alacaklar', '', '', ''],
            ['      Grup Firmalardan Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Diğer Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Şüpheli Ticari Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Şüpheli Ticari Alacak Karşılığı (-)', '', '', ''],
            ['      Alacak Reeskontu (-)', '', '', ''],
            ['    İmtiyaz Sözleşmelerine İlişkin Finansal Varlıklar', '', '', ''],
            ['  Diğer Alacaklar', '', '', ''],
            ['    Diğer Taraflardan Alacaklar', '', '', ''],
            ['      Verilen Depozito ve Teminatlar', '', '', ''],
            ['      Vergi Dairesinden Alacaklar', '', '', ''],
            ['      Şüpheli Diğer Alacaklar', '', '', ''],
            ['      Şüpheli Diğer Alacaklar Karşılığı (-)', '', '', ''],
            ['      Diğer Alacak Reeskontu (-)', '', '', ''],
            ['      Diğer Çeşitli Alacaklar', '', '', ''],
            ['    İlişkili Diğer Taraflardan Alacaklar', '', '', ''],
            ['      Ortaklardan Alacaklar', '', '', ''],
            ['      Bağlı Ortaklıklardan, İştiraklerden ve Müşterek Girişimlerden Alacaklar', '', '', ''],
            ['      Ödeme Çağrısı Yapılmış Sermaye Alacağı', '', '', ''],
            ['      Personelden Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Diğer Çeşitli Alacaklar', '', '', ''],
            ['    Devam Eden İnşaat Sözleşmelerinden Alacaklar', '', '', ''],
            ['      Devam Eden İnşaat ve Taahhüt İşlerinden Doğan Sözleşme Varlıkları', '', '', ''],
            ['      Mal ve Hizmet Satışlarından Doğan Sözleşme Varlıkları', '', '', ''],
            ['      Diğer Sözleşme Varlıkları', '', '', ''],
            ['  Stoklar', '', '', ''],
            ['    İlk Madde ve Malzeme', '', '', ''],
            ['    Yarı Mamuller', '', '', ''],
            ['    Ara Mamuller', '', '', ''],
            ['    Mamuller', '', '', ''],
            ['    Emtia', '', '', ''],
            ['    Diğer Stoklar', '', '', ''],
            ['    Stok Değer Düşüklüğü Karşılığı (-)', '', '', ''],
            ['    Verilen Sipariş Avansları', '', '', ''],
            ['  Canlı Varlıklar', '', '', ''],
            ['  Peşin Ödenmiş Giderler', '', '', ''],
            ['    Gelecek Aylara Ait Giderler', '', '', ''],
            ['    Verilen Sipariş Avansları (159)', '', '', ''],
            ['    Verilen Duran Varlık Avansları (259)', '', '', ''],
            ['  Peşin Ödenmiş Vergi ve Benzerleri', '', '', ''],
            ['    Peşin Ödenen Vergiler', '', '', ''],
            ['    Diğer', '', '', ''],
            ['  Diğer Dönen Varlıklar', '', '', ''],
            ['    Gelir Tahakkukları', '', '', ''],
            ['    Devreden KDV', '', '', ''],
            ['    Diğer KDV', '', '', ''],
            ['    Personel Avansları', '', '', ''],
            ['    İş Avansları', '', '', ''],
            ['    Diğer Dönen Varlıklar', '', '', ''],
            ['  Satılmaya Hazır Duran Varlıklar', '', '', ''],
            
            // VARLIKLAR / DURAN VARLIKLAR
            ['DURAN VARLIKLAR', '', '', ''],
            ['  Ticari Alacaklar', '', '', ''],
            ['    İlişkili Olmayan Taraflardan Ticari Alacaklar', '', '', ''],
            ['      Alıcılar', '', '', ''],
            ['      Alacak Senetleri', '', '', ''],
            ['      Alacak Reeskontu (-)', '', '', ''],
            ['      Şüpheli Ticari Alacaklar', '', '', ''],
            ['      Şüpheli Ticari Alacaklar Karşılığı (-)', '', '', ''],
            ['    İlişkili Taraflardan Ticari Alacaklar', '', '', ''],
            ['      Ortaklardan Alacaklar', '', '', ''],
            ['      Grup Firmalardan Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Diğer Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Şüpheli Ticari Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Şüpheli Ticari Alacak Karşılığı (-)', '', '', ''],
            ['      Alacak Reeskontu (-)', '', '', ''],
            ['    İmtiyaz Sözleşmelerine İlişkin Finansal Varlıklar', '', '', ''],
            ['  Diğer Alacaklar', '', '', ''],
            ['    Diğer Taraflardan Alacaklar', '', '', ''],
            ['      Verilen Depozito ve Teminatlar', '', '', ''],
            ['      Vergi Dairesinden Alacaklar', '', '', ''],
            ['      Şüpheli Diğer Alacaklar', '', '', ''],
            ['      Şüpheli Diğer Alacaklar Karşılığı (-)', '', '', ''],
            ['      Diğer Alacak Reeskontu (-)', '', '', ''],
            ['      Ortaklardan Alacaklar', '', '', ''],
            ['    İlişkili Taraflardan Diğer Alacaklar', '', '', ''],
            ['      Ortaklardan Diğer Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Şüpheli Alacaklar', '', '', ''],
            ['      İlişkili Taraflardan Şüpheli Alacak Karşılığı (-)', '', '', ''],
            ['      İlişkili Taraflardan Alacak Reeskontu (-)', '', '', ''],
            ['      Diğer', '', '', ''],
            ['    Devam Eden İnşaat Sözleşmelerinden Alacaklar', '', '', ''],
            ['      Devam Eden İnşaat ve Taahhüt İşlerinden Doğan Sözleşme Varlıkları', '', '', ''],
            ['      Mal ve Hizmet Satışlarından Doğan Sözleşme Varlıkları', '', '', ''],
            ['      Diğer Sözleşme Varlıkları', '', '', ''],
            ['  Finansal Yatırımlar', '', '', ''],
            ['    Bağlı Ortaklıklardaki Yatırımlar', '', '', ''],
            ['    İştiraklerdeki ve Müşterek Girişimlerdeki Yatırımlar', '', '', ''],
            ['    Diğer Finansal Yatırımlar', '', '', ''],
            ['    Özkaynak Yöntemi ile Değerlenen Yatırımlar', '', '', ''],
            ['    Sermaye Taahhütleri (-)', '', '', ''],
            ['    Değer Düşüklüğü Karşılığı (-)', '', '', ''],
            ['  Türev Araçlar', '', '', ''],
            ['  Teminata Verilen Finansal Varlıklar', '', '', ''],
            ['  İştirakler, İş ve Bağlı Ortaklıklardaki Yatırımlar', '', '', ''],
            ['  Canlı Varlıklar', '', '', ''],
            ['  Yatırım Amaçlı Gayrimenkuller', '', '', ''],
            ['  Maddi Duran Varlıklar', '', '', ''],
            ['    Arazi ve Arsalar', '', '', ''],
            ['    Yer Altı ve Yer Üstü Düzenleri', '', '', ''],
            ['    Binalar', '', '', ''],
            ['    Tesis, Makine ve Cihazlar', '', '', ''],
            ['    Taşıtlar', '', '', ''],
            ['    Demirbaşlar', '', '', ''],
            ['    Özel Maliyetler', '', '', ''],
            ['    Diğer Maddi Duran Varlıklar', '', '', ''],
            ['    Finansal Kiralama Yoluyla İktisap Edilen Varlıklar', '', '', ''],
            ['    Birikmiş Amortismanlar (-)', '', '', ''],
            ['    Maddi Duran Varlıklar Değer Düşüklüğü Karşılığı (-)', '', '', ''],
            ['    Yapılmakta Olan Yatırımlar', '', '', ''],
            ['    Verilen Duran Varlık Avansları', '', '', ''],
            ['  Maddi Olmayan Duran Varlıklar', '', '', ''],
            ['    Gayrimaddi Haklar', '', '', ''],
            ['    Kuruluş ve Örgütlenme Giderleri', '', '', ''],
            ['    Özel Maliyetler', '', '', ''],
            ['    Geliştirme Giderleri', '', '', ''],
            ['    Diğer Maddi Olmayan Varlıklar', '', '', ''],
            ['    İtfa Payları (-)', '', '', ''],
            ['    Maddi Olmayan Duran Varlıklar Değer Düşüklüğü Karşılığı (-)', '', '', ''],
            ['  Şerefiye', '', '', ''],
            ['  Kullanım Hakkı Varlıkları', '', '', ''],
            ['  Peşin Ödenmiş Giderler', '', '', ''],
            ['    Gelecek Yıllara Ait Giderler', '', '', ''],
            ['    Verilen Duran Varlık Avansları', '', '', ''],
            ['  Ertelenmiş Vergi Varlığı', '', '', ''],
            ['  Diğer Duran Varlıklar', '', '', ''],
            ['    296 Geçici Hesap', '', '', ''],
            ['  TOPLAM', '', '', ''],
            ['TOPLAM VARLIKLAR', '', '', ''],
            
            // KAYNAKLAR (PASİF)
            ['KAYNAKLAR', '', '', ''],
            ['  KISA VADELİ YÜKÜMLÜLÜKLER', '', '', ''],
            ['    Finansal Yükümlülükler', '', '', ''],
            ['      Kısa Vadeli Finans Kuruluşlarına Borçlar', '', '', ''],
            ['      İhraç Edilen Menkul Kıymetler', '', '', ''],
            ['      Paylara Dönüştürülebilir Borçlanma Araçları', '', '', ''],
            ['      Türev Araçlardan Borçlar', '', '', ''],
            ['      Diğer Finansal Yükümlülükler', '', '', ''],
            ['      Finansal Kiralama İşlemlerinden Borçlar', '', '', ''],
            ['      Ertelenmiş Finansal Kiralama Borçlanma Maliyeti (-)', '', '', ''],
            ['      Uzun Vadeli Kredilerin Anapara Taksitleri ve Faizleri', '', '', ''],
            ['      Diğer Mali Borçlar', '', '', ''],
            ['      Faaliyet Kiralamasına İlişkin Borçlar', '', '', ''],
            ['      Diğer, Kredi Karı Borçları', '', '', ''],
            ['      Diğer', '', '', ''],
            ['      Faaliyet Kiralamasına İlişkin Borçlar', '', '', ''],
            ['      Faaliyet Kiralamasına İlişkin Borçlar', '', '', ''],
            ['    Ticari Borçlar', '', '', ''],
            ['      İlişkili Olmayan Taraflara Ticari Borçlar', '', '', ''],
            ['        Satıcılar', '', '', ''],
            ['        Borç Senetleri', '', '', ''],
            ['        Diğer Ticari Borçlar', '', '', ''],
            ['        Borç Reeskontu (-)', '', '', ''],
            ['      İlişkili Taraflara Ticari Borçlar', '', '', ''],
            ['        Ortaklara Borçlar', '', '', ''],
            ['        Grup Firmalara Borçlar', '', '', ''],
            ['        Borç Reeskontu (-)', '', '', ''],
            ['    Ödenecek Vergi ve Benzerleri', '', '', ''],
            ['      Dönem Karı Vergi Yükümlülüğü', '', '', ''],
            ['      Ödenecek Vergi ve Fonlar', '', '', ''],
            ['      Ödenecek SSK', '', '', ''],
            ['      Vadesi Geçmiş Taksitlendirilmiş Vergi ve Fonlar', '', '', ''],
            ['    Diğer Borçlar', '', '', ''],
            ['      Diğer Taraflara Borçlar', '', '', ''],
            ['        Alınan Depozito ve Teminatlar', '', '', ''],
            ['        Diğer Borç Prekontu (-)', '', '', ''],
            ['        Diğer Çeşitli Borçlar', '', '', ''],
            ['        Ortaklara Borçlar', '', '', ''],
            ['        Ortaklara Borçlar', '', '', ''],
            ['        Bağlı Ortaklıklara, İştiraklere ve Müşterek Girişimlere Borçlar', '', '', ''],
            ['        Personele Borçlar', '', '', ''],
            ['      Devam Eden İnşaat Sözleşmelerinden Borçlar', '', '', ''],
            ['        Devam Eden İnşaat ve Taahhüt İşlerinden Doğan Sözleşme Yükümlülükleri', '', '', ''],
            ['        Mal ve Hizmet Satışlarından Doğan Sözleşme Yükümlülükleri', '', '', ''],
            ['        Diğer Sözleşme Yükümlülükleri', '', '', ''],
            ['      Alınan Avanslar', '', '', ''],
            ['        Gelecek Aylara Ait Gelirler', '', '', ''],
            ['        Alınan Avanslar', '', '', ''],
            ['      Dönem Karı Vergi Yükümlülüğü', '', '', ''],
            ['        Vergi Karşılığı', '', '', ''],
            ['        Peşin Ödenen Vergiler (-)', '', '', ''],
            ['      Kısa Vadeli Karşılıklar', '', '', ''],
            ['        Kısa Vadeli Borç ve Gider Karşılıkları', '', '', ''],
            ['          Borç ve Gider Karşılıkları', '', '', ''],
            ['          Borç ve Gider Tahakkukları', '', '', ''],
            ['          Kıdem Tazminatı Karşılığı', '', '', ''],
            ['          Kıdem Tazminatı Karşılığı', '', '', ''],
            ['          Yıllık İzin Karşılıkları', '', '', ''],
            ['      Diğer Kısa Vadeli Yükümlülükler', '', '', ''],
            ['        Tecil Terkin KDV', '', '', ''],
            ['        Diğer Çeşitli Kısa Vadeli Borçlar', '', '', ''],
            ['        Diğer KDV', '', '', ''],
            ['        Ara Toplam', '', '', ''],
            ['        Ertelenmiş Gelirler', '', '', ''],
            ['  UZUN VADELİ YÜKÜMLÜLÜKLER', '', '', ''],
            ['    Finansal Yükümlülükler', '', '', ''],
            ['      Uzun Vadeli Finans Kuruluşlarına Borçlar', '', '', ''],
            ['      İhraç Edilen Menkul Kıymetler', '', '', ''],
            ['      Paylara Dönüştürülebilir Borçlanma Araçları', '', '', ''],
            ['      Türev Araçlardan Borçlar', '', '', ''],
            ['      Diğer Uzun Vadeli Finansal Yükümlülükler', '', '', ''],
            ['      Finansal Kiralama İşlemlerinden Borçlar', '', '', ''],
            ['      Ertelenmiş Finansal Kiralama Borçlanma Maliyeti (-)', '', '', ''],
            ['      Uzun Vadeli Kredilerin Anapara Taksitleri ve Faizleri', '', '', ''],
            ['      Diğer Mali Borçlar', '', '', ''],
            ['      Faaliyet Kiralamasına İlişkin U.V. Borçlar', '', '', ''],
            ['      Diğer', '', '', ''],
            ['      Diğer', '', '', ''],
            ['      Faaliyet Kiralamasına İlişkin U.V. Borçlar', '', '', ''],
            ['    Ticari Borçlar', '', '', ''],
            ['      İlişkili Olmayan Taraflara Ticari Borçlar', '', '', ''],
            ['        Satıcılar', '', '', ''],
            ['        Borç Senetleri', '', '', ''],
            ['        Borç Reeskontu (-)', '', '', ''],
            ['      İlişkili Taraflara Ticari Borçlar', '', '', ''],
            ['        Ortaklara Borçlar', '', '', ''],
            ['        Grup Firmalara Borçlar', '', '', ''],
            ['        Borç Reeskontu (-)', '', '', ''],
            ['    Diğer Borçlar', '', '', ''],
            ['      Diğer Taraflara Borçlar', '', '', ''],
            ['        Alınan Depozito ve Teminatlar', '', '', ''],
            ['        Ortaklara Borçlar', '', '', ''],
            ['        Kamuya Olan Ertelenmiş Taksitlendirilmiş Borçlar', '', '', ''],
            ['        Borç Reeskontu (-)', '', '', ''],
            ['        Ortaklara Borçlar', '', '', ''],
            ['        Ortaklara Borçlar', '', '', ''],
            ['        Grup Firmalara Borçlar', '', '', ''],
            ['        İlişkili Taraflara Borçlar Prekontu', '', '', ''],
            ['      Ertelenmiş Gelirler (Alınan Avanslar)', '', '', ''],
            ['        Gelecek Yıllara Ait Gelirler', '', '', ''],
            ['        Alınan Avanslar', '', '', ''],
            ['    Uzun Vadeli Karşılıklar', '', '', ''],
            ['      Diğer Uzun Vadeli Borç ve Gider Karşılıkları', '', '', ''],
            ['        Borç ve Gider Karşılıkları', '', '', ''],
            ['        Diğer Borç ve Gider Tahakkukları', '', '', ''],
            ['        Kıdem Tazminatı Karşılığı', '', '', ''],
            ['        Kıdem Tazminatı', '', '', ''],
            ['    Ödenecek Vergi ve Yükümlülükler', '', '', ''],
            ['      Ertelenmiş Vergi Yükümlülüğü', '', '', ''],
            ['    Diğer Uzun Vadeli Yükümlülükler', '', '', ''],
            ['      Diğer', '', '', ''],
            ['  ÖZKAYNAKLAR', '', '', ''],
            ['    Ana Ortaklığa Ait Özkaynaklar', '', '', ''],
            ['      Sermaye', '', '', ''],
            ['        Ödenmiş Sermaye', '', '', ''],
            ['        Ödenmemiş Sermaye (-)', '', '', ''],
            ['        Sermaye Düzeltme Farkları', '', '', ''],
            ['        Geri Alınmış Paylar (-)', '', '', ''],
            ['        Paylara İlişkin Primler', '', '', ''],
            ['        Yeniden Değerleme Yedeği (Kar/Zararda Yeniden Sınıflandırılacak)', '', '', ''],
            ['        Yeniden Değerleme ve Ölçüm Kazanç/Kayıpları', '', '', ''],
            ['        Özkaynak Yöntemi ile Değerlenen Yatırımlar', '', '', ''],
            ['        Emeklilik Planlarında Aktüeryal Kayıp/Kazançlar Fonu', '', '', ''],
            ['        Korunma Yedeği (Kar/Zararda Yeniden Sınıflandırılmayacak)', '', '', ''],
            ['        Yabancı Para Çevrim Farkları', '', '', ''],
            ['        Kayda Alınan Emtia Özel Karşılık Hesabı', '', '', ''],
            ['        Yeniden Değerleme ve Sınıflandırma Kazanç/Kayıpları', '', '', ''],
            ['      Kar Yedekleri', '', '', ''],
            ['        Yasal Yedekler', '', '', ''],
            ['        Özel Fonlar / Statü Yedekleri', '', '', ''],
            ['        Olağanüstü Yedekler / Diğer Kar Yedekleri', '', '', ''],
            ['      Geçmiş Yıllar Kar / Zararları', '', '', ''],
            ['        Diğer', '', '', ''],
            ['        Aktüeryal Kayıp ve Kazanç', '', '', ''],
            ['        Geçmiş Yıllar Karları / (Zararları)', '', '', ''],
            ['      Net Dönem Karı / Zararı (-)', '', '', ''],
            ['        Ödenen Kar Payı Avansı', '', '', ''],
            ['        Kontrol Gücü Olmayan Paylar', '', '', ''],
            ['TOPLAM KAYNAKLAR', '', '', ''],
        ];
    }

    /**
     * Headings
     */
    public function headings(): array
    {
        return [
            'Hesap Adı',
            'Bağımsız Denetimden Geçmiş Cari Dönem (YYYY-MM-DD)',
            'Bağımsız Denetimden Geçmiş Önceki Dönem (YYYY-MM-DD)',
            'Bağımsız Denetimden Geçmiş Açılış Bakiyeleri (YYYY-MM-DD)',
        ];
    }

    /**
     * Column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 80,
            'B' => 50,
            'C' => 50,
            'D' => 50,
        ];
    }

    /**
     * Styles
     */
    public function styles(Worksheet $sheet)
    {
        // Heading style
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Grup başlıkları (büyük harfle başlayanlar)
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $value = $sheet->getCell("A{$row}")->getValue();
            
            // DÖNEN VARLIKLAR, DURAN VARLIKLAR, KAYNAKLAR gibi ana gruplar (boşluksuz)
            if ($value && !preg_match('/^\s+/', $value)) {
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F5F5F5'],
                    ],
                ]);
            }
            
            // Alt gruplar (2 boşluk ile başlayanlar)
            if ($value && preg_match('/^  [A-Z]/', $value)) {
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);
            }
        }

        return $sheet;
    }
}
