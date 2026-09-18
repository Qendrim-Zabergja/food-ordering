import { Navigate, Route, Routes } from 'react-router'
import { Layout } from './components/Layout'
import { RedirectIfAuthenticated, RequireAuth } from './components/Guards'
import { Login } from './pages/Login'
import { Menu } from './pages/Menu'
import { Register } from './pages/Register'
import { RouteName } from './enums/RouteName'
import './App.css'

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path={RouteName.HOME} element={<Navigate to={RouteName.MENU} replace />} />

        <Route
          path={RouteName.LOGIN}
          element={
            <RedirectIfAuthenticated>
              <Login />
            </RedirectIfAuthenticated>
          }
        />

        <Route
          path={RouteName.REGISTER}
          element={
            <RedirectIfAuthenticated>
              <Register />
            </RedirectIfAuthenticated>
          }
        />

        <Route
          path={RouteName.MENU}
          element={
            <RequireAuth>
              <Menu />
            </RequireAuth>
          }
        />

        <Route
          path="*"
          element={
            <div className="state">
              <h2>Page not found</h2>
            </div>
          }
        />
      </Route>
    </Routes>
  )
}
