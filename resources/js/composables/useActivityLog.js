/**
 * Activity Log Composable
 * 
 * Reusable function to log user activities across all modules
 * Sends activity data to activity_logs table via API
 * 
 * @example
 * import { logActivity } from '@/composables/useActivityLog'
 * 
 * logActivity('view', 'products', { product_id: 1, product_name: 'Soap' })
 */

export const logActivity = async (action, module, details) => {
    try {
        const response = await fetch(route('products.log-activity'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                action,
                module,
                details,
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to log activity');
        }

        return await response.json();
    } catch (error) {
        console.error('Activity log error:', error);
        return { success: false, error: error.message };
    }
};

/**
 * Available Actions:
 * - 'view' - Viewing a record
 * - 'create' - Creating a new record
 * - 'edit' - Editing an existing record
 * - 'delete' - Deleting a record
 * - 'duplicate' - Duplicating a record
 * - 'update_status' - Updating status
 * - 'export' - Exporting data
 * - 'import' - Importing data
 * 
 * Available Modules:
 * - 'products'
 * - 'brands'
 * - 'categories'
 * - 'purchase_orders'
 * - 'goods_received_notes'
 * - 'sales'
 * - 'customers'
 * - 'suppliers'
 * - etc.
 */
