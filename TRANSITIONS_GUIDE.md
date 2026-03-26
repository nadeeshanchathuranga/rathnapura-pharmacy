# Global Transitions Setup Guide

## ✅ What's Been Done

1. **Created Global CSS File**: `resources/css/transitions.css`
   - Contains all transition animations
   - Automatically available across the entire project

2. **Imported into Main CSS**: Added to `resources/css/app.css`
   - No need to import in individual files
   - Automatically loaded with your app

3. **Updated Products Index**: Added transitions to all modals

## 🎨 Available Transition Classes

### Modal Transitions
```vue
<!-- Best for modals/dialogs -->
<Transition name="modal-scale">
  <YourModal v-if="isOpen" />
</Transition>
```

### Other Available Transitions
- `modal-fade` - Simple fade in/out
- `slide-up` - Slide from bottom (mobile modals)
- `slide-right` - Slide from right (sidebars)
- `fade` - Quick fade
- `zoom` - Zoom in/out effect
- `bounce` - Bouncy entrance (success messages)
- `notification` - Slide from top (alerts)
- `flip` - Card flip effect

### List Item Transitions
```vue
<!-- For adding/removing list items -->
<TransitionGroup name="list-item" tag="tbody">
  <tr v-for="item in items" :key="item.id">
    ...
  </tr>
</TransitionGroup>
```

## 📝 How to Add to Other Pages

### Step 1: No CSS Import Needed!
The transitions.css is already globally available. You don't need to import anything.

### Step 2: Wrap Your Modal with Transition

**Before:**
```vue
<YourModal
  v-model:open="isModalOpen"
  :someProps="data"
/>
```

**After:**
```vue
<Transition name="modal-scale">
  <YourModal
    v-if="isModalOpen"
    v-model:open="isModalOpen"
    :someProps="data"
  />
</Transition>
```

**Important:** Change `v-model:open` to use `v-if` for the Transition to work!

## 🔧 Quick Implementation Guide for Each Page

### For All Index Pages (List Views)

1. **Find your modals** (usually at the bottom of template)
2. **Wrap each modal** with `<Transition name="modal-scale">`
3. **Change the visibility logic**:
   - From: Component always rendered with `v-model:open`
   - To: Component conditionally rendered with `v-if` + `v-model:open`

### Example Pattern:

```vue
<template>
  <AppLayout>
    <!-- Your page content -->
    
    <!-- Modals -->
    <Transition name="modal-scale">
      <CreateModal v-if="isCreateOpen" v-model:open="isCreateOpen" />
    </Transition>
    
    <Transition name="modal-scale">
      <EditModal v-if="isEditOpen" v-model:open="isEditOpen" />
    </Transition>
    
    <Transition name="modal-scale">
      <ViewModal v-if="isViewOpen" v-model:open="isViewOpen" />
    </Transition>
  </AppLayout>
</template>
```

## 🎯 Files to Update

Apply the same pattern to these files:

### Products Section (✅ DONE)
- `Products/Index.vue`

### Other Sections to Update:
1. `ProductTransferRequests/Index.vue`
2. `StockTransferReturns/Index.vue`
3. `Returns/Index.vue`
4. `PurchaseOrderRequests/Index.vue`
5. `PurchaseOrders/Index.vue`
6. `GoodsReceivedNotes/Index.vue`
7. `Sales/Index.vue`
8. `Customers/Index.vue`
9. `Suppliers/Index.vue`
10. `Categories/Index.vue`
11. `Brands/Index.vue`
... and any other pages with modals

## 🚀 Advanced Usage

### Different Transitions for Different Modals

```vue
<!-- Create modal with scale effect -->
<Transition name="modal-scale">
  <CreateModal v-if="isCreateOpen" />
</Transition>

<!-- Delete confirmation with zoom effect -->
<Transition name="zoom">
  <DeleteModal v-if="isDeleteOpen" />
</Transition>

<!-- Notification with slide from top -->
<Transition name="notification">
  <SuccessMessage v-if="showSuccess" />
</Transition>
```

### For Tables with Add/Remove Rows

```vue
<table>
  <thead>...</thead>
  <TransitionGroup name="list-item" tag="tbody">
    <tr v-for="item in items" :key="item.id">
      ...
    </tr>
  </TransitionGroup>
</table>
```

## 🎨 Customizing Transitions

To customize timing or effects, edit `resources/css/transitions.css`:

```css
/* Make modal transitions faster */
.modal-scale-enter-active {
  transition: all 0.2s ease; /* Changed from 0.3s */
}

/* Add your own custom transition */
.my-custom-enter-active {
  transition: all 0.5s ease;
}
.my-custom-enter-from {
  opacity: 0;
  transform: scale(0.5) rotate(45deg);
}
```

## ⚡ Performance Tips

1. **Use v-if with Transitions**: Always use `v-if` (not `v-show`) with `<Transition>`
2. **Unique Keys**: When using `<TransitionGroup>`, ensure each item has a unique `:key`
3. **Avoid Deep Nesting**: Don't nest multiple transitions unnecessarily

## 🐛 Troubleshooting

### Transition Not Working?
1. Check if you're using `v-if` (not `v-show`)
2. Verify the modal component has a single root element
3. Ensure transition name matches a CSS class in transitions.css

### Jumpy Animation?
1. Make sure the modal container doesn't have conflicting CSS transitions
2. Check that the modal's `open` prop changes before the `v-if` renders

### No Animation on First Load?
- This is normal! Transitions only work when elements are added/removed from DOM, not on initial page load

## 📚 Additional Resources

- Vue Transition Docs: https://vuejs.org/guide/built-ins/transition.html
- CSS Transitions: https://developer.mozilla.org/en-US/docs/Web/CSS/transition

---

**That's it!** Your transitions are now globally available. Just wrap your modals with `<Transition>` and enjoy smooth animations! 🎉
