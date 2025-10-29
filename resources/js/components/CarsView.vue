<template>
  <div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>{{ $t('cars.title') }}</h2>
      <button class="btn btn-primary" @click="showAddModal = true">
        {{ $t('cars.addNew') }}
      </button>
    </div>

    <!-- Filter -->
    <div class="row mb-3">
      <div class="col-md-6">
        <input 
          type="text" 
          class="form-control" 
          :placeholder="$t('cars.searchPlaceholder')"
          v-model="filters.search"
          @input="() => loadCars(1)"
          :maxlength="MAX_SEARCH_LENGTH"
        >
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="filters.is_registered" @change="() => loadCars(1)">
          <option value="">{{ $t('cars.allCars') }}</option>
          <option value="1">{{ $t('common.registered') }}</option>
          <option value="0">{{ $t('common.unregistered') }}</option>
        </select>
      </div>
    </div>

    <!-- Cars Table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th @click="sortBy('name')" class="sortable" :class="{ 'sorted': sorting.sortBy === 'name' }">
              {{ $t('cars.tableHeaders.name') }}
              <i v-if="sorting.sortBy === 'name'" :class="sorting.sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
              <i v-else class="fa fa-sort text-muted"></i>
            </th>
            <th @click="sortBy('registration_number')" class="sortable" :class="{ 'sorted': sorting.sortBy === 'registration_number' }">
              {{ $t('cars.tableHeaders.registrationNumber') }}
              <i v-if="sorting.sortBy === 'registration_number'" :class="sorting.sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
              <i v-else class="fa fa-sort text-muted"></i>
            </th>
            <th @click="sortBy('is_registered')" class="sortable" :class="{ 'sorted': sorting.sortBy === 'is_registered' }">
              {{ $t('cars.tableHeaders.registered') }}
              <i v-if="sorting.sortBy === 'is_registered'" :class="sorting.sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
              <i v-else class="fa fa-sort text-muted"></i>
            </th>
            <th @click="sortBy('parts_count')" class="sortable" :class="{ 'sorted': sorting.sortBy === 'parts_count' }">
              {{ $t('cars.tableHeaders.partsCount') }}
              <i v-if="sorting.sortBy === 'parts_count'" :class="sorting.sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
              <i v-else class="fa fa-sort text-muted"></i>
            </th>
            <th>{{ $t('cars.tableHeaders.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="car in cars.data" :key="car.id">
            <td :title="car.name">{{ truncateText(car.name) }}</td>
            <td>{{ car.registration_number || '-' }}</td>
            <td>
              <span class="badge" :class="car.is_registered ? 'bg-success' : 'bg-secondary'">
                {{ car.is_registered ? $t('common.yes') : $t('common.no') }}
              </span>
            </td>
            <td>{{ car.parts_count || (car.parts ? car.parts.length : 0) }}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary me-2" @click="editCar(car)">
                {{ $t('common.edit') }}
              </button>
              <button class="btn btn-sm btn-outline-danger" @click="deleteCar(car)">
                {{ $t('common.delete') }}
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
          <button class="page-link" @click="loadCars(cars.current_page - 1)">{{ $t('common.previous') }}</button>
        </li>
        <li class="page-item" v-for="page in getPageNumbers()" :key="page" :class="{ active: page === cars.current_page }">
          <button class="page-link" @click="loadCars(page)">{{ page }}</button>
        </li>
        <li class="page-item" :class="{ disabled: cars.current_page === cars.last_page }">
          <button class="page-link" @click="loadCars(cars.current_page + 1)">{{ $t('common.next') }}</button>
        </li>
      </ul>
    </nav>

    <!-- Add/Edit Modal -->
    <div class="modal fade" :class="{ show: showAddModal || showEditModal }" :style="{ display: (showAddModal || showEditModal) ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingCar ? $t('cars.editTitle') : $t('cars.addNew') }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCar">
              <div class="mb-3">
                <label class="form-label">{{ $t('cars.carName') }} *</label>
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
                    {{ $t('cars.carIsRegistered') }}
                  </label>
                </div>
              </div>
              <div class="mb-3" v-if="form.is_registered">
                <label class="form-label">{{ $t('cars.registrationNumber') }} *</label>
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
                <button type="button" class="btn btn-secondary" @click="closeModal">{{ $t('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ $t('common.save') }}</button>
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
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { translateValidationErrors } from '../utils/validationTranslator'
import { MAX_SEARCH_LENGTH, MAX_STRING_VIEW_LENGTH, PAGINATION_RANGE, MESSAGE_DISPLAY_DURATION } from '../constants'

export default {
  name: 'CarsView',
  setup() {
    const { t } = useI18n()

    const cars = ref({ data: [], current_page: 1, last_page: 1 })
    const showAddModal = ref(false)
    const showEditModal = ref(false)
    const editingCar = ref(null)
    
    const filters = reactive({
      search: '',
      is_registered: ''
    })

    const sorting = reactive({
      sortBy: 'name',
      sortDirection: 'asc'
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

    const showMessage = (text, type = 'error') => {
      if (window.showGlobalMessage) {
        window.showGlobalMessage(text, type)
        setTimeout(() => {
          if (window.showGlobalMessage) {
            window.showGlobalMessage('', '')
          }
        }, MESSAGE_DISPLAY_DURATION)
      }
    }

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
        
        params.append('sort_by', sorting.sortBy)
        params.append('sort_direction', sorting.sortDirection)
        
        const response = await axios.get(`/api/cars?${params}`)
        if (response.data.error) {
          const key = response.data.message || 'messages.error.retrieveCars'
          showMessage(t(key), 'error')
          cars.value = { data: [], current_page: 1, last_page: 1 }
        } else {
          cars.value = response.data
        }
      } catch (error) {
        console.error('Error loading cars:', error)
        const key = error.response?.data?.message || 'messages.error.network'
        showMessage(t(key), 'error')
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
        const response = editingCar.value
          ? await axios.put(`/api/cars/${editingCar.value.id}`, form)
          : await axios.post('/api/cars', form)
        
        if (response.data.success && response.data.message) {
          showMessage(t(response.data.message), 'success')
        }
        
        closeModal()
        loadCars(cars.value.current_page)
      } catch (error) {
        console.error('Error saving car:', error)
        
        if (error.response && error.response.status === 422 && error.response.data.errors) {
          const validationErrors = translateValidationErrors(error.response.data.errors, t)
          if (validationErrors.name) {
            errors.name = validationErrors.name
          }
          if (validationErrors.registration_number) {
            errors.registration_number = validationErrors.registration_number
          }
        } else {
          const key = error.response?.data?.message || 'messages.error.network'
          showMessage(t(key), 'error')
        }
      }
    }

    const deleteCar = async (car) => {
      if (confirm(t('cars.deleteConfirm', { name: car.name }))) {
        try {
          const response = await axios.delete(`/api/cars/${car.id}`)
          if (response.data.success && response.data.message) {
            showMessage(t(response.data.message), 'success')
          }
          loadCars(cars.value.current_page)
        } catch (error) {
          console.error('Error deleting car:', error)
          const key = error.response?.data?.message || 'messages.error.network'
          showMessage(t(key), 'error')
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

    const sortBy = (field) => {
      if (sorting.sortBy === field) {
        sorting.sortDirection = sorting.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        sorting.sortBy = field
        sorting.sortDirection = 'asc'
      }
      loadCars(1)
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
      sorting,
      form,
      errors,
      loadCars,
      editCar,
      saveCar,
      deleteCar,
      closeModal,
      getPageNumbers,
      truncateText,
      sortBy,
      MAX_SEARCH_LENGTH
    }
  }
}
</script>
