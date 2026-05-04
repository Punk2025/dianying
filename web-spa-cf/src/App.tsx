import { useMemo } from 'react';
import { DabaoLayout } from './layout/DabaoLayout';
import { HomeDabao } from './pages/HomeDabao';
import { dabaoAsset } from './publicUrls';

function resolveSearchPrefix(): string {
  const explicit = import.meta.env.VITE_SEARCH_PREFIX;
  if (explicit && explicit.length > 0) {
    return explicit;
  }
  const origin = import.meta.env.VITE_API_ORIGIN || '';
  if (import.meta.env.DEV) {
    return '/legacy-api/index.php?search-&keyword=';
  }
  if (origin) {
    return `${origin.replace(/\/$/, '')}/index.php?search-&keyword=`;
  }
  return '/index.php?search-&keyword=';
}

export function App() {
  const siteName = import.meta.env.VITE_SITE_NAME || '站点';
  const siteUrl = import.meta.env.VITE_SITE_URL || '/';
  const logoUrl = import.meta.env.VITE_LOGO_URL || dabaoAsset('Images/logo.png');
  const mobileQrUrl = import.meta.env.VITE_MOBILE_QR_URL || dabaoAsset('Images/erweima.png');
  const searchPrefix = useMemo(() => resolveSearchPrefix(), []);

  return (
    <DabaoLayout
      siteName={siteName}
      siteUrl={siteUrl}
      logoUrl={logoUrl}
      mobileQrUrl={mobileQrUrl}
      categories={[]}
      searchPrefix={searchPrefix}
    >
      <HomeDabao />
    </DabaoLayout>
  );
}
