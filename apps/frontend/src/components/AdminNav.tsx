import { NavLink } from 'react-router'
import { useAuth } from '../hooks/useAuth'
import { RouteName } from '../enums/RouteName'
import { PermissionSlug } from '../enums/PermissionSlug'

function linkClass({ isActive }: { isActive: boolean }): string {
  return isActive ? 'chip chip--active' : 'chip'
}

/**
 * Each tab appears only for someone holding the matching permission, so an
 * administrator who can manage orders but not the menu is not shown a section
 * the API would refuse anyway.
 */
export function AdminNav() {
  const { can } = useAuth()

  return (
    <nav className="admin-nav">
      {can(PermissionSlug.MANAGE_ORDERS) ? (
        <NavLink to={RouteName.ADMIN_ORDERS} className={linkClass}>
          Orders
        </NavLink>
      ) : null}

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
