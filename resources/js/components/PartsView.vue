<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>{{ $t('parts.title') }}</h2>
      <button class="btn btn-primary" @click="showAddModal = true">
        {{ $t('parts.addNew') }}
      </button>
    </div>

    <!-- Filter -->
    <div class="row mb-3">
      <div class="col-md-6">
        <input 
          type="text" 
          class="form-control" 
          :placeholder="$t('parts.searchPlaceholder')"
          v-model="filters.search"
          @input="loadParts"
          :maxlength="MAX_SEARCH_LENGTH"
        >
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="filters.car_id" @change="loadParts">
          <option value="">{{ $t('parts.allCars') }}</option>
          <option v-for="car in allCars" :key="car.id" :value="car.id" :title="car.name">
            {{ truncateText(car.name) }}
          </option>
        </select>
      </div>
    </div>

    <!-- Parts Table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th @click="sortBy('name')" class="sortable" :class="{ 'sorted': sorting.sortBy === 'name' }">
              {{ $t('parts.tableHeaders.partName') }}
              <i v-if="sorting.sortBy === 'name'" :class="sorting.sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
              <i v-else class="fa fa-sort text-muted"></i>
            </th>
            <th @click="sortBy('serialnumber')" class="sortable" :class="{ 'sorted': sorting.sortBy === 'serialnumber' }">
              {{ $t('parts.tableHeaders.serialNumber') }}
              <i v-if="sorting.sortBy === 'serialnumber'" :class="sorting.sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
              <i v-else class="fa fa-sort text-muted"></i>
            </th>
            <th @click="sortBy('car_name')" class="sortable" :class="{ 'sorted': sorting.sortBy === 'car_name' }">
              {{ $t('parts.tableHeaders.car') }}
              <i v-if="sorting.sortBy === 'car_name'" :class="sorting.sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
              <i v-else class="fa fa-sort text-muted"></i>
            </th>
            <th>{{ $t('parts.tableHeaders.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="part in parts.data" :key="part.id" 
              :class="{ 
                'table-danger': part.deleted_at, 
                'table-warning': !part.deleted_at && part.car?.deleted_at 
              }">
            <td :title="part.name">
              {{ truncateText(part.name) }}
              <span v-if="part.deleted_at" class="badge bg-danger ms-2">{{ $t('common.deleted') }}</span>
            </td>
            <td :title="part.serialnumber">{{ truncateText(part.serialnumber) }}</td>
            <td :title="part.car?.name">
              {{ truncateText(part.car?.name || '-') }}
              <span v-if="part.car?.deleted_at" class="badge bg-danger ms-2">{{ $t('common.carDeleted') }}</span>
            </td>
            <td>
              <button v-if="!part.deleted_at" class="btn btn-sm btn-outline-primary me-2" @click="editPart(part)">
                {{ $t('common.edit') }}
              </button>
              <button v-if="!part.deleted_at" class="btn btn-sm btn-outline-danger me-2" @click="deletePart(part)">
                {{ $t('common.delete') }}
              </button>
              <button v-if="part.deleted_at && part.car && !part.car.deleted_at" class="btn btn-sm btn-outline-success" @click="restorePart(part)">
                {{ $t('common.restore') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="parts.last_page > 1">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: parts.current_page === 1 }">
          <button class="page-link" @click="loadParts(parts.current_page - 1)">{{ $t('common.previous') }}</button>
        </li>
        <li class="page-item" v-for="page in getPageNumbers()" :key="page" :class="{ active: page === parts.current_page }">
          <button class="page-link" @click="loadParts(page)">{{ page }}</button>
        </li>
        <li class="page-item" :class="{ disabled: parts.current_page === parts.last_page }">
          <button class="page-link" @click="loadParts(parts.current_page + 1)">{{ $t('common.next') }}</button>
        </li>
      </ul>
    </nav>

    <!-- Add/Edit Modal -->
    <div class="modal fade" :class="{ show: showAddModal || showEditModal }" :style="{ display: (showAddModal || showEditModal) ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingPart ? $t('parts.editTitle') : $t('parts.addNew') }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="savePart">
              <div class="mb-3">
                <label class="form-label">{{ $t('parts.partName') }} *</label>
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
                <label class="form-label">{{ $t('parts.serialNumber') }} *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.serialnumber }"
                  v-model="form.serialnumber" 
                  @input="errors.serialnumber = null"
                  required
                >
                <div v-if="errors.serialnumber" class="invalid-feedback">
                  {{ errors.serialnumber[0] }}
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">{{ $t('parts.car') }} *</label>
                <select 
                  class="form-select" 
                  :class="{ 'is-invalid': errors.car_id }"
                  v-model="form.car_id" 
                  @change="errors.car_id = null"
                  required
                >
                  <option value="">{{ $t('parts.selectCar') }}</option>
                  <option v-for="car in allCars" :key="car.id" :value="car.id" :title="car.name">
                    {{ truncateText(car.name) }}
                  </option>
                </select>
                <div v-if="errors.car_id" class="invalid-feedback">
                  {{ errors.car_id[0] }}
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
  name: 'PartsView',
  setup() {
    const { t } = useI18n()

    const parts = ref({ data: [], current_page: 1, last_page: 1 })
    const allCars = ref([])
    const showAddModal = ref(false)
    const showEditModal = ref(false)
    const editingPart = ref(null)
    
    const filters = reactive({
      search: '',
      car_id: ''
    })

    const sorting = reactive({
      sortBy: 'name',
      sortDirection: 'asc'
    })

    const form = reactive({
      name: '',
      serialnumber: '',
      car_id: ''
    })

    const errors = reactive({
      name: null,
      serialnumber: null,
      car_id: null
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

    const loadParts = async (page = 1) => {
      try {
        const params = new URLSearchParams({
          page: page.toString()
        })
        
        if (filters.search) {
          params.append('search', filters.search)
        }
        
        if (filters.car_id) {
          params.append('car_id', filters.car_id)
        }
        
        params.append('sort_by', sorting.sortBy)
        params.append('sort_direction', sorting.sortDirection)
        
        const response = await axios.get(`/api/parts?${params}`)
        if (response.data.error) {
          const key = response.data.message || 'messages.error.retrieveParts'
          showMessage(t(key), 'error')
          parts.value = { data: [], current_page: 1, last_page: 1 }
        } else {
          parts.value = response.data
        }
      } catch (error) {
        console.error('Error loading parts:', error)
        const key = error.response?.data?.message || 'messages.error.network'
        showMessage(t(key), 'error')
      }
    }

    const loadCars = async () => {
      try {
        const response = await axios.get('/api/cars-all')
        allCars.value = response.data
      } catch (error) {
        console.error('Error loading cars:', error)
      }
    }

    const editPart = (part) => {
      editingPart.value = part
      form.name = part.name
      form.serialnumber = part.serialnumber
      form.car_id = part.car_id
      showEditModal.value = true
    }

    const savePart = async () => {
      errors.name = null
      errors.serialnumber = null
      errors.car_id = null

      try {
        const response = editingPart.value
          ? await axios.put(`/api/parts/${editingPart.value.id}`, form)
          : await axios.post('/api/parts', form)
        
        if (response.data.success && response.data.message) {
          showMessage(t(response.data.message), 'success')
        }
        
        closeModal()
        loadParts(parts.value.current_page)
      } catch (error) {
        console.error('Error saving part:', error)
        
        if (error.response && error.response.status === 422 && error.response.data.errors) {
          const validationErrors = translateValidationErrors(error.response.data.errors, t)
          if (validationErrors.name) {
            errors.name = validationErrors.name
          }
          if (validationErrors.serialnumber) {
            errors.serialnumber = validationErrors.serialnumber
          }
          if (validationErrors.car_id) {
            errors.car_id = validationErrors.car_id
          }
        } else {
          const key = error.response?.data?.message || 'messages.error.network'
          showMessage(t(key), 'error')
        }
      }
    }

    const deletePart = async (part) => {
      if (confirm(t('parts.deleteConfirm', { name: part.name }))) {
        try {
          const response = await axios.delete(`/api/parts/${part.id}`)
          if (response.data.success && response.data.message) {
            showMessage(t(response.data.message), 'success')
          }
          loadParts(parts.value.current_page)
        } catch (error) {
          console.error('Error deleting part:', error)
          const key = error.response?.data?.message || 'messages.error.network'
          showMessage(t(key), 'error')
        }
      }
    }

    const restorePart = async (part) => {
      try {
        const response = await axios.post(`/api/parts/${part.id}/restore`)
        if (response.data.success && response.data.message) {
          showMessage(t(response.data.message), 'success')
        }
        loadParts(parts.value.current_page)
      } catch (error) {
        console.error('Error restoring part:', error)
        const key = error.response?.data?.message || 'messages.error.network'
        showMessage(t(key), 'error')
      }
    }

    const closeModal = () => {
      showAddModal.value = false
      showEditModal.value = false
      editingPart.value = null
      form.name = ''
      form.serialnumber = ''
      form.car_id = ''
      errors.name = null
      errors.serialnumber = null
      errors.car_id = null
    }

    const truncateText = (text) => {
      if (!text) return '-'
      return text.length > MAX_STRING_VIEW_LENGTH ? text.substring(0, MAX_STRING_VIEW_LENGTH) + '...' : text
    }

    const getPageNumbers = () => {
      const pages = []
      const start = Math.max(1, parts.value.current_page - PAGINATION_RANGE)
      const end = Math.min(parts.value.last_page, parts.value.current_page + PAGINATION_RANGE)
      
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
      loadParts(1)
    }

    onMounted(() => {
      loadParts()
      loadCars()
    })

    return {
      parts,
      allCars,
      showAddModal,
      showEditModal,
      editingPart,
      filters,
      sorting,
      form,
      errors,
      loadParts,
      loadCars,
      editPart,
      savePart,
      deletePart,
      restorePart,
      closeModal,
      getPageNumbers,
      truncateText,
      sortBy,
      MAX_SEARCH_LENGTH
    }
  }
}
</script>
