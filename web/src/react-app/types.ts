export type ProductCategory = string;

export interface Product {
  id: string;
  name: string;
  category: string;
  categoryLabel: string;
  badge: string;
  highlight: string;
  specLabel: string;
  specValue: string;
  description: string;
  images: string[];
  specs: {
    material?: string;
    rpmOptions?: string;
    temperatureRange?: string;
    weightOptions?: string;
    finish?: string;
    technology?: string;
    flowRateOptions?: string;
    chipset?: string;
    bearingType?: string;
    waterPumpImpeller?: string;
    [key: string]: string | undefined;
  };
  compatibility: string[];
  marketplaceLinks: {
    shopee?: string;
    tokopedia?: string;
    tiktok?: string;
    whatsapp?: string;
  };
}

export interface WorkshopPartner {
  id: string;
  name: string;
  region: 'sumatera' | 'jawa-bali' | 'kal-sul-papua';
  regionLabel: string;
  city: string;
  address: string;
  specialization: string;
  phone: string;
  verified: boolean;
}

export interface ConsultationFormData {
  fullName: string;
  phone: string;
  motorType: string;
  category: string;
  message: string;
}
