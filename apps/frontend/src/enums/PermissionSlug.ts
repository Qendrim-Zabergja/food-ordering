/**
 * Mirrors app/Enums/PermissionSlug.php on the API.
 *
 * These gate what the interface offers. They are not a security boundary - the
 * server checks the same permission again through a Policy on every request,
 * and that check is the one that counts.
 */
export const PermissionSlug = {
  VIEW_PRODUCTS: 'view-products',
  MANAGE_PRODUCTS: 'manage-products',
  VIEW_PRODUCT_CATEGORIES: 'view-product-categories',
  MANAGE_PRODUCT_CATEGORIES: 'manage-product-categories',
  VIEW_ORDERS: 'view-orders',
  MANAGE_ORDERS: 'manage-orders',
} as const

export type PermissionSlug = (typeof PermissionSlug)[keyof typeof PermissionSlug]
