<template>
  <div id="app">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container">
        <a class="navbar-brand" href="#" @click="currentView = 'cars'">
          Car Parts Inventory Management
        </a>
        <div class="navbar-nav">
          <a class="nav-link" href="#" @click="currentView = 'cars'" :class="{ active: currentView === 'cars' }">
            Cars
          </a>
          <a class="nav-link" href="#" @click="currentView = 'parts'" :class="{ active: currentView === 'parts' }">
            Parts
          </a>
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
import { ref } from 'vue'
import CarsView from './components/CarsView.vue'
import PartsView from './components/PartsView.vue'

export default {
  name: 'App',
  components: {
    CarsView,
    PartsView
  },
  setup() {
    const currentView = ref('cars')

    return {
      currentView
    }
  }
}
</script>