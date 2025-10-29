import { createI18n } from 'vue-i18n'
import en from './locales/en.js'
import sk from './locales/sk.js'

const savedLocale = localStorage.getItem('locale') || 'sk'

export default createI18n({
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: {
    en,
    sk,
  },
  legacy: false,
})

