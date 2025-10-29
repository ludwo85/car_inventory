<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Parts Management</h2>
      <button class="btn btn-primary" @click="showAddModal = true">
        Add New Part
      </button>
    </div>

    <!-- Filter -->
    <div class="row mb-3">
      <div class="col-md-6">
        <input 
          type="text" 
          class="form-control" 
          placeholder="Search by name, serial number or car..."
          v-model="filters.search"
          @input="loadParts"
        >
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="filters.car_id" @change="loadParts">
          <option value="">All Cars</option>
          <option v-for="car in allCars" :key="car.id" :value="car.id">
            {{ car.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Parts Table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Part Name</th>
            <th>Serial Number</th>
            <th>Car</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="part in parts.data" :key="part.id">
            <td>{{ part.name }}</td>
            <td>{{ part.serialnumber }}</td>
            <td>{{ part.car.name }}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary me-2" @click="editPart(part)">
                Edit
              </button>
              <button class="btn btn-sm btn-outline-danger" @click="deletePart(part)">
                Delete
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
          <button class="page-link" @click="loadParts(parts.current_page - 1)">Previous</button>
        </li>
        <li class="page-item" v-for="page in getPageNumbers()" :key="page" :class="{ active: page === parts.current_page }">
          <button class="page-link" @click="loadParts(page)">{{ page }}</button>
        </li>
        <li class="page-item" :class="{ disabled: parts.current_page === parts.last_page }">
          <button class="page-link" @click="loadParts(parts.current_page + 1)">Next</button>
        </li>
      </ul>
    </nav>

    <!-- Add/Edit Modal -->
    <div class="modal fade" :class="{ show: showAddModal || showEditModal }" :style="{ display: (showAddModal || showEditModal) ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingPart ? 'Edit Part' : 'Add New Part' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="savePart">
              <div class="mb-3">
                <label class="form-label">Part Name *</label>
                <input type="text" class="form-control" v-model="form.name" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Serial Number *</label>
                <input type="text" class="form-control" v-model="form.serialnumber" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Car *</label>
                <select class="form-select" v-model="form.car_id" required>
                  <option value="">Select Car</option>
                  <option v-for="car in allCars" :key="car.id" :value="car.id">
                    {{ car.name }}
                  </option>
                </select>
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
  name: 'PartsView',
  setup() {
    const PAGINATION_RANGE = 2

    const parts = ref({ data: [], current_page: 1, last_page: 1 })
    const allCars = ref([])
    const showAddModal = ref(false)
    const showEditModal = ref(false)
    const editingPart = ref(null)
    
    const filters = reactive({
      search: '',
      car_id: ''
    })

    const form = reactive({
      name: '',
      serialnumber: '',
      car_id: ''
    })

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
        
        const response = await axios.get(`/api/parts?${params}`)
        parts.value = response.data
      } catch (error) {
        console.error('Error loading parts:', error)
        alert('Error loading parts')
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
      try {
        if (editingPart.value) {
          await axios.put(`/api/parts/${editingPart.value.id}`, form)
        } else {
          await axios.post('/api/parts', form)
        }
        
        closeModal()
        loadParts(parts.value.current_page)
      } catch (error) {
        console.error('Error saving part:', error)
        alert('Error saving part')
      }
    }

    const deletePart = async (part) => {
      if (confirm(`Are you sure you want to delete part "${part.name}"?`)) {
        try {
          await axios.delete(`/api/parts/${part.id}`)
          loadParts(parts.value.current_page)
        } catch (error) {
          console.error('Error deleting part:', error)
          alert('Error deleting part')
        }
      }
    }

    const closeModal = () => {
      showAddModal.value = false
      showEditModal.value = false
      editingPart.value = null
      form.name = ''
      form.serialnumber = ''
      form.car_id = ''
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
      form,
      loadParts,
      loadCars,
      editPart,
      savePart,
      deletePart,
      closeModal,
      getPageNumbers
    }
  }
}
</script>
