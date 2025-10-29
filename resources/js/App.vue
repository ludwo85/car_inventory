<template>
  <div id="app">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container">
        <a class="navbar-brand" href="#" @click="currentView = 'cars'">
          {{ $t('app.title') }}
        </a>
        <div class="navbar-nav d-flex flex-row gap-2">
          <a class="nav-link" href="#" @click="currentView = 'cars'" :class="{ active: currentView === 'cars' }">
            {{ $t('app.cars') }}
          </a>
          <a class="nav-link" href="#" @click="currentView = 'parts'" :class="{ active: currentView === 'parts' }">
            {{ $t('app.parts') }}
          </a>
          <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle ms-2" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              {{ currentLocale.toUpperCase() }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
              <li><a class="dropdown-item" href="#" @click.prevent="setLocale('sk')">SK</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="setLocale('en')">EN</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <div class="container mt-4">
      <CarsView v-if="currentView === 'cars'" />
      <PartsView v-if="currentView === 'parts'" />
    </div>

    <!-- Global Message Display - Bottom Left -->
    <div v-if="globalMessage.text" 
         class="position-fixed bottom-0 start-0 m-3" 
         style="z-index: 1050; max-width: 400px;">
      <div class="alert shadow-sm" 
           :class="globalMessage.type === 'success' ? 'alert-success' : 'alert-danger'" 
           role="alert">
        <div class="d-flex align-items-center">
          <div class="flex-grow-1">
            {{ globalMessage.text }}
          </div>
          <button type="button" 
                  class="btn-close ms-2" 
                  @click="clearGlobalMessage" 
                  aria-label="Close"></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CarsView from './components/CarsView.vue'
import PartsView from './components/PartsView.vue'
import { MESSAGE_DISPLAY_DURATION } from './constants'

export default {
  name: 'App',
  components: {
    CarsView,
    PartsView
  },
  setup() {
    const { locale } = useI18n()
    const currentView = ref('cars')
    const globalMessage = ref({ text: '', type: '' })

    const currentLocale = computed(() => locale.value)

    const setLocale = (lang) => {
      locale.value = lang
      localStorage.setItem('locale', lang)
    }

    const clearGlobalMessage = () => {
      globalMessage.value = { text: '', type: '' }
    }
    window.showGlobalMessage = (text, type = 'error') => {
      globalMessage.value = { text, type }
      setTimeout(() => {
        globalMessage.value = { text: '', type: '' }
      }, MESSAGE_DISPLAY_DURATION)
    }

    return {
      currentView,
      currentLocale,
      setLocale,
      globalMessage,
      clearGlobalMessage
    }
  }
}
</script>