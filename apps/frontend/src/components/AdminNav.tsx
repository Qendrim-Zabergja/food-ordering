import { NavLink } from 'react-router'
import { useAuth } from '../hooks/useAuth'
import { RouteName } from '../enums/RouteName'
import { PermissionSlug } from '../enums/PermissionSlug'

function linkClass({ isActive }: { isActive: boolean }): string {
  return isActive ? 'chip chip--active' : 'chip'
}

/**
 * The admin area covers the menu only - orders are managed from the Orders page,
 * where an administrator already sees every order. Each tab appears only for
 * someone holding the matching permission.
 */
export function AdminNav() {
  const { can } = useAuth()

  return (
    <nav className="admin-nav">
      {can(PermissionSlug.MANAGE_PRODUCTS) ? (
        <NavLink to={RouteName.ADMIN_PRODUCTS} className={linkClass}>
          Products
        </NavLink>
      ) : null}

      {can(PermissionSlug.MANAGE_PRODUCT_CATEGORIES) ? (
        <NavLink to={RouteName.ADMIN_CATEGORIES} className={linkClass}>
          Categories
        </NavLink>
      ) : null}
    </nav>
  )
}
