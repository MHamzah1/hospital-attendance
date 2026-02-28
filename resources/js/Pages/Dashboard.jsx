import AdminDashboard from '@/Pages/Dashboard/AdminDashboard';
import EmployeeDashboard from '@/Pages/Dashboard/EmployeeDashboard';

export default function Dashboard(props) {
    if (props.role === 'admin') {
        return <AdminDashboard {...props} />;
    }

    return <EmployeeDashboard {...props} />;
}
