/**
 * Every path in the application.
 *
 * Navigation never uses a raw string - components import from here, so a path
 * can be changed in one place and the compiler finds every use.
 *
 * Written as a const object rather than a TypeScript enum because tsconfig sets
 * `erasableSyntaxOnly`, which forbids enums. The call site reads identically.
 */
export const RouteName = {
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  MENU: '/menu',
  CART: '/cart',
  CHECKOUT: '/checkout',
  ORDERS: '/orders',
  ORDER: '/orders/:id',
  ADMIN: '/admin',
  ADMIN_PRODUCTS: '/admin/products',
  ADMIN_CATEGORIES: '/admin/categories',
  ADMIN_ORDERS: '/admin/orders',
} as const

export type RouteName = (typeof RouteName)[keyof typeof RouteName]

/** Fills the :id in RouteName.ORDER. */
export function orderPath(id: string): string {
  return RouteName.ORDER.replace(':id', id)
}
