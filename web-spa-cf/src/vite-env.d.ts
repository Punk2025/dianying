/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_ORIGIN: string;
  readonly VITE_SITE_NAME?: string;
  readonly VITE_SITE_URL?: string;
  readonly VITE_SEARCH_PREFIX?: string;
  readonly VITE_LOGO_URL?: string;
  readonly VITE_MOBILE_QR_URL?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
