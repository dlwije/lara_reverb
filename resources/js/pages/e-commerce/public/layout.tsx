import Banner from '@/components/e-commerce/template/banner';
import Navbar from '@/components/e-commerce/template/navBar';
import Footer from '@/components/e-commerce/template/footer';
import { Header } from '@/components/e-commerce/template/Header';

export default function PublicLayout({ children }) {

    return (
        <>
            <Banner />
            <Header />
            {/*<TopBar />*/}
            {/*<Navbar />*/}
            {children}
            <Footer />
        </>
    );
}
