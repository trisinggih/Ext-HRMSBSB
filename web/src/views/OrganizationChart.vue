<template>
  <div class="py-2">
    <div class="d-flex justify-content-between align-items-center mb-4 px-4">
      <h4 class="font-size-18 mb-0">Struktur Organisasi</h4>
    </div>
    
    <div 
      v-if="tree.length" 
      ref="scrollContainer"
      class="overflow-auto min-h-[500px]" 
      style="cursor: grab; user-select: none;"
      @mousedown="startDrag"
      @mouseleave="stopDrag"
      @mouseup="stopDrag"
      @mousemove="drag"
    >
      <div class="org-tree d-inline-block min-w-100">
        <ul class="d-flex justify-content-center">
          <OrgNode v-for="node in tree" :key="`${node.type}-${node.id}`" :node="node" :depth="0" />
        </ul>
      </div>
    </div>
    <div v-else class="flex justify-center items-center h-64">
      <div class="text-slate-500 text-lg flex items-center gap-3">
        <i class="mdi mdi-loading mdi-spin text-2xl text-blue-500"></i> Memuat struktur...
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'
import OrgNode from '../components/OrgNode.vue'

const tree = ref([])
const scrollContainer = ref(null)

let isDown = false;
let startX;
let scrollLeft;

const startDrag = (e) => {
  isDown = true;
  scrollContainer.value.style.cursor = 'grabbing';
  startX = e.pageX - scrollContainer.value.offsetLeft;
  scrollLeft = scrollContainer.value.scrollLeft;
}
const stopDrag = () => {
  isDown = false;
  scrollContainer.value.style.cursor = 'grab';
}
const drag = (e) => {
  if (!isDown) return;
  e.preventDefault();
  const x = e.pageX - scrollContainer.value.offsetLeft;
  const walk = (x - startX) * 2; // scroll speed
  scrollContainer.value.scrollLeft = scrollLeft - walk;
}

onMounted(async () => {
  try {
    const { data } = await api.get('/organization-chart')
    tree.value = data.data || []
  } catch (e) {
    console.error('Failed to load org chart', e)
  }
})
</script>

<style>
/* CSS Tree Layout */
.org-tree ul {
  padding-top: 20px;
  position: relative;
  transition: all 0.5s;
  display: flex;
  padding-left: 0;
  margin-bottom: 0;
}

.org-tree li {
  float: left;
  text-align: center;
  list-style-type: none;
  position: relative;
  padding: 20px 10px 0 10px;
  transition: all 0.5s;
}

/* We will use ::before and ::after to draw the connectors */
.org-tree li::before, .org-tree li::after {
  content: '';
  position: absolute;
  top: 0;
  right: 50%;
  border-top: 2px solid #cbd5e1;
  width: 50%;
  height: 20px;
}
.org-tree li::after {
  right: auto;
  left: 50%;
  border-left: 2px solid #cbd5e1;
}

/* We need to remove left-right connectors from elements without any siblings */
.org-tree li:only-child::after, .org-tree li:only-child::before {
  display: none;
}

/* Remove space from the top of single children */
.org-tree li:only-child {
  padding-top: 0;
}

/* Remove left connector from first child and right connector from last child */
.org-tree li:first-child::before, .org-tree li:last-child::after {
  border: 0 none;
}

/* Adding back the vertical line to the last nodes */
.org-tree li:last-child::before {
  border-right: 2px solid #cbd5e1;
  border-radius: 0 5px 0 0;
}
.org-tree li:first-child::after {
  border-radius: 5px 0 0 0;
}

/* Time to add downward connectors from parents */
.org-tree ul ul::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  border-left: 2px solid #cbd5e1;
  width: 0;
  height: 20px;
}
</style>
