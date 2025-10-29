<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Car Management</h2>
      <button class="btn btn-primary" @click="showAddModal = true">
        Add New Car
      </button>
    </div>

    <!-- Filter -->
    <div class="row mb-3">
      <div class="col-md-6">
        <input 
          type="text" 
          class="form-control" 
          placeholder="Search by name or registration number..."
          v-model="filters.search"
          @input="() => loadCars(1)"
          :maxlength="MAX_SEARCH_LENGTH"
        >
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="filters.is_registered" @change="() => loadCars(1)">
          <option value="">All Cars</option>
          <option value="1">Registered</option>
          <option value="0">Unregistered</option>
        </select>
      </div>
    </div>

    <!-- Cars Table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Name</th>
            <th>Registration Number</th>
            <th>Registered</th>
            <th>Parts Count</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="car in cars.data" :key="car.id">
            <td :title="car.name">{{ truncateText(car.name) }}</td>
            <td>{{ car.registration_number || '-' }}</td>
            <td>
              <span class="badge" :class="car.is_registered ? 'bg-success' : 'bg-secondary'">
                {{ car.is_registered ? 'Yes' : 'No' }}
              </span>
            </td>
            <td>{{ car.parts.length }}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary me-2" @click="editCar(car)">
                Edit
              </button>
              <button class="btn btn-sm btn-outline-danger" @click="deleteCar(car)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="cars.last_page > 1">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: cars.current_page === 1 }">
          <button class="page-link" @click="loadCars(cars.current_page - 1)">Previous</button>
        </li>
        <li class="page-item" v-for="page in getPageNumbers()" :key="page" :class="{ active: page === cars.current_page }">
          <button class="page-link" @click="loadCars(page)">{{ page }}</button>
        </li>
        <li class="page-item" :class="{ disabled: cars.current_page === cars.last_page }">
          <button class="page-link" @click="loadCars(cars.current_page + 1)">Next</button>
        </li>
      </ul>
    </nav>

    <!-- Add/Edit Modal -->
    <div class="modal fade" :class="{ show: showAddModal || showEditModal }" :style="{ display: (showAddModal || showEditModal) ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingCar ? 'Edit Car' : 'Add New Car' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCar">
              <div class="mb-3">
                <label class="form-label">Car Name *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.name }"
                  v-model="form.name" 
                  @input="errors.name = null"
                  required
                >
                <div v-if="errors.name" class="invalid-feedback">
                  {{ errors.name[0] }}
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="form.is_registered" id="is_registered" @change="errors.registration_number = null">
                  <label class="form-check-label" for="is_registered">
                    Car is registered
                  </label>
                </div>
              </div>
              <div class="mb-3" v-if="form.is_registered">
                <label class="form-label">Registration Number *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.registration_number }"
                  v-model="form.registration_number" 
                  @input="form.registration_number = form.registration_number.toUpperCase(); errors.registration_number = null"
                  required
                  maxlength="7"
                  placeholder="AA000XX"
                >
                <div v-if="errors.registration_number" class="invalid-feedback">
                  {{ errors.registration_number[0] }}
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade" :class="{ show: showAddModal || showEditModal }" v-if="showAddModal || showEditModal"></div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'CarsView',
  setup() {
    const PAGINATION_RANGE = 2
    const MAX_SEARCH_LENGTH = 30
    const MAX_STRING_VIEW_LENGTH = 50

    const cars = ref({ data: [], current_page: 1, last_page: 1 })
    const showAddModal = ref(false)
    const showEditModal = ref(false)
    const editingCar = ref(null)
    
    const filters = reactive({
      search: '',
      is_registered: ''
    })

    const form = reactive({
      name: '',
      registration_number: '',
      is_registered: false
    })

    const errors = reactive({
      name: null,
      registration_number: null
    })

    const loadCars = async (page = 1) => {
      try {
        const params = new URLSearchParams({
          page: page.toString()
        })
        
        if (filters.search) {
          params.append('search', filters.search)
        }
        if (filters.is_registered !== '') {
          params.append('is_registered', filters.is_registered)
        }
        
        const response = await axios.get(`/api/cars?${params}`)
        cars.value = response.data
      } catch (error) {
        console.error('Error loading cars:', error)
        alert('Error loading cars')
      }
    }

    const editCar = (car) => {
      editingCar.value = car
      form.name = car.name
      form.registration_number = car.registration_number || ''
      form.is_registered = car.is_registered
      showEditModal.value = true
    }

    const saveCar = async () => {
      errors.name = null
      errors.registration_number = null

      if (!form.is_registered) {
        form.registration_number = ''
      }

      try {
        if (editingCar.value) {
          await axios.put(`/api/cars/${editingCar.value.id}`, form)
        } else {
          await axios.post('/api/cars', form)
        }
        
        closeModal()
        loadCars(cars.value.current_page)
      } catch (error) {
        console.error('Error saving car:', error)
        
        if (error.response && error.response.status === 422 && error.response.data.errors) {
          const validationErrors = error.response.data.errors
          if (validationErrors.name) {
            errors.name = validationErrors.name
          }
          if (validationErrors.registration_number) {
            errors.registration_number = validationErrors.registration_number
          }
        } else {
          alert('Error saving car')
        }
      }
    }

    const deleteCar = async (car) => {
      if (confirm(`Are you sure you want to delete car "${car.name}"?`)) {
        try {
          await axios.delete(`/api/cars/${car.id}`)
          loadCars(cars.value.current_page)
        } catch (error) {
          console.error('Error deleting car:', error)
          alert('Error deleting car')
        }
      }
    }

    const closeModal = () => {
      showAddModal.value = false
      showEditModal.value = false
      editingCar.value = null
      form.name = ''
      form.registration_number = ''
      form.is_registered = false
      errors.name = null
      errors.registration_number = null
    }

    const truncateText = (text) => {
      if (!text) return '-'
      return text.length > MAX_STRING_VIEW_LENGTH ? text.substring(0, MAX_STRING_VIEW_LENGTH) + '...' : text
    }

    const getPageNumbers = () => {
      const pages = []
      const start = Math.max(1, cars.value.current_page - PAGINATION_RANGE)
      const end = Math.min(cars.value.last_page, cars.value.current_page + PAGINATION_RANGE)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    }

    onMounted(() => {
      loadCars()
    })

    return {
      cars,
      showAddModal,
      showEditModal,
      editingCar,
      filters,
      form,
      errors,
      loadCars,
      editCar,
      saveCar,
      deleteCar,
      closeModal,
      getPageNumbers,
      truncateText,
      MAX_SEARCH_LENGTH
    }
  }
}
</script>
