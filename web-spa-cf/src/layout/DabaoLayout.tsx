import type { ReactNode } from 'react';
import { DabaoFooter } from './DabaoFooter';
import { DabaoHeader, type NavCate } from './DabaoHeader';

type Props = {
  siteName: string;
  siteUrl: string;
  logoUrl: string;
  mobileQrUrl: string;
  categories?: NavCate[];
  searchPrefix: string;
  children: ReactNode;
};

export function DabaoLayout({
  siteName,
  siteUrl,
  logoUrl,
  mobileQrUrl,
  categories = [],
  searchPrefix,
  children,
}: Props) {
  return (
    <>
      <DabaoHeader
        siteName={siteName}
        siteUrl={siteUrl}
        logoUrl={logoUrl}
        mobileQrUrl={mobileQrUrl}
        categories={categories}
        searchPrefix={searchPrefix}
      />
      {children}
      <DabaoFooter siteName={siteName} />
    </>
  );
}
