<template>
  <li>
    <!-- Node Card -->
    <div class="inline-block relative">
      <div 
        class="org-node-card min-w-[200px] max-w-[240px] bg-white rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-y-1 hover:shadow-md cursor-pointer overflow-hidden"
        @click="open = !open"
      >
        <!-- Header Banner -->
        <div class="h-2 w-full" :class="bannerColor"></div>
        
        <div class="p-3">
          <!-- Icon / Avatar -->
          <div class="flex justify-center mb-2">
            <div v-if="node.type === 'employee'" class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-lg font-bold text-slate-600 border border-slate-200">
              {{ initials }}
            </div>
            <div v-else class="w-10 h-10 rounded-full flex items-center justify-center text-xl bg-opacity-10" :class="iconColors">
              <i class="mdi" :class="iconName"></i>
            </div>
          </div>
          
          <!-- Node Type Label -->
          <div class="text-[10px] font-bold uppercase tracking-widest mb-1 text-center" :class="textColor">
            {{ node.type }}
          </div>
          
          <!-- Node Name -->
          <div class="text-sm font-semibold text-slate-800 text-center leading-tight">
            {{ node.name }}
          </div>
          
          <!-- Employee ID (Optional) -->
          <div v-if="node.employee_id" class="text-xs text-slate-500 mt-1 text-center font-medium">
            {{ node.employee_id }}
          </div>
          
          <!-- Child Count Badge -->
          <div v-if="hasChildren" class="mt-3 flex justify-center">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
              {{ node.children.length }} bawahan
            </span>
          </div>
        </div>
        
        <!-- Toggle button if has children -->
        <div 
          v-if="hasChildren" 
          class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-6 h-6 bg-white border border-slate-200 rounded-full flex items-center justify-center shadow-sm text-slate-400 hover:text-blue-500 hover:border-blue-300 transition-colors z-10"
        >
          <i :class="open ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down'"></i>
        </div>
      </div>
    </div>
    
    <!-- Children -->
    <ul v-if="open && hasChildren">
      <OrgNode v-for="ch in node.children" :key="ch.type + '-' + ch.id" :node="ch" :depth="depth + 1" />
    </ul>
  </li>
</template>

<script>
export default {
  name: 'OrgNode',
  props: { node: Object, depth: { type: Number, default: 0 } },
  data() { return { open: true } },
  computed: {
    hasChildren() { return this.node.children && this.node.children.length },
    initials() {
      if (!this.node.name) return 'U';
      return this.node.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    },
    bannerColor() {
      return {
        company: 'bg-blue-600',
        branch: 'bg-cyan-500',
        department: 'bg-purple-500',
        division: 'bg-orange-500',
        position: 'bg-emerald-500',
        employee: 'bg-slate-400',
      }[this.node.type] || 'bg-slate-300'
    },
    iconColors() {
      return {
        company: 'bg-blue-600 text-blue-600',
        branch: 'bg-cyan-500 text-cyan-500',
        department: 'bg-purple-500 text-purple-500',
        division: 'bg-orange-500 text-orange-500',
        position: 'bg-emerald-500 text-emerald-500',
      }[this.node.type] || 'bg-slate-500 text-slate-500'
    },
    textColor() {
      return {
        company: 'text-blue-600',
        branch: 'text-cyan-600',
        department: 'text-purple-600',
        division: 'text-orange-600',
        position: 'text-emerald-600',
        employee: 'text-slate-500',
      }[this.node.type] || 'text-slate-500'
    },
    iconName() {
      return {
        company: 'mdi-domain',
        branch: 'mdi-office-building-marker',
        department: 'mdi-account-group',
        division: 'mdi-source-branch',
        position: 'mdi-badge-account-horizontal',
      }[this.node.type] || 'mdi-folder'
    }
  },
}
</script>
