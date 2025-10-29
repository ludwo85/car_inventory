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
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CarsView from './components/CarsView.vue'
import PartsView from './components/PartsView.vue'

export default {
  name: 'App',
  components: {
    CarsView,
    PartsView
  },
  setup() {
    const { locale } = useI18n()
    const currentView = ref('cars')

    const currentLocale = computed(() => locale.value)

    const setLocale = (lang) => {
      locale.value = lang
      localStorage.setItem('locale', lang)
    }

    return {
      currentView,
      currentLocale,
      setLocale
    }
  }
}
</script>