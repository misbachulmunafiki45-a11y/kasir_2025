<template>
    <Head>
        <title>Transactions - Aplikasi Kasir</title>
    </Head>
    <main class="c-main">
        <div class="container-fluid">
            <div class="fade-in">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-0 rounded-3 shadow">
                            <div class="card-body">

                                <div class="mb-3 position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-barcode"></i></span>
                                        <input type="text" class="form-control" v-model="barcode" @input="onBarcodeInput" @keyup.enter="searchProduct" placeholder="Input Nama Barang">
                                    </div>
                                    <div v-if="suggestionsVisible" class="list-group position-absolute w-100" style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                        <button type="button"
                                                v-for="item in suggestions"
                                                :key="item.id"
                                                class="list-group-item list-group-item-action"
                                                @click="selectSuggestion(item)">
                                            <div class="fw-bold">{{ item.title }}</div>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Product Name</label>
                                    <input type="text" class="form-control" :value="product.title" placeholder="Product Name" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Qty</label>
                                    <input type="number" class="form-control text-center" v-model="qty" placeholder="Qty" min="1">
                                </div>
                                <div class="text-end">
                                    <button @click.prevent="clearSearch" class="btn btn-warning btn-md border-0 shadow text-uppercase mt-3 me-2" :disabled="!product.id">CLEAR</button>
                                    <button @click.prevent="addToCart" class="btn btn-success btn-md border-0 shadow text-uppercase mt-3" :disabled="!product.id">ADD ITEM</button>
                                </div>

                            </div>
                        </div>

                        <!-- Reprint Receipt Container (kiri) -->
                        <div class="card border-0 rounded-3 shadow mt-3">
                            <div class="card-body position-relative">
                                <h5 class="fw-bold mb-3">Cetak Ulang Struk</h5>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Nama Customer</label>
                                        <input type="text" class="form-control" v-model="reprintInvoice" @input="onInvoiceInput" placeholder="Ketik nama customer (atau nomor invoice)">
                                        <div v-if="invoiceSuggestionsVisible" class="list-group position-absolute w-100" style="z-index: 1050; max-height: 220px; overflow-y: auto;">
                                            <button type="button"
                                                    v-for="item in invoiceSuggestions"
                                                    :key="item.invoice"
                                                    class="list-group-item list-group-item-action"
                                                    @click="selectInvoiceSuggestion(item)">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold">{{ item.customer || '-' }}</span>
                                                    <span>Rp. {{ formatPrice(item.grand_total) }}</span>
                                                </div>
                                                <div class="text-muted">{{ item.invoice }} · {{ item.date }}</div>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button @click.prevent="reprintReceipt" class="btn btn-success btn-md border-0 shadow text-uppercase w-100">Cetak Ulang</button>
                                    </div>
                                </div>
                                <div class="text-muted mt-2" style="font-size: .9rem;">Masukkan nama customer untuk mencari transaksi. Anda juga bisa mengetik nomor invoice jika diinginkan.</div>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-8">

                        <div v-if="session.error" class="alert alert-danger">
                            {{ session.error }}
                        </div>

                        <div v-if="session.success" class="alert alert-success">
                            {{ session.success }}
                        </div>

                        <div class="card border-0 rounded-3 shadow border-top-success">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 col-4">
                                        <h4 class="fw-bold">GRAND TOTAL</h4>
                                    </div>
                                    <div class="col-md-8 col-8 text-end">
                                        <h4 class="fw-bold">Rp. {{ formatPrice(grandTotal) }}</h4>
                                        <div v-if="change > 0">
                                            <hr>
                                            <h5 class="text-success">Change : <strong>Rp. {{ formatPrice(change) }}</strong></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 rounded-3 shadow">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="fw-bold">Cashier</label>
                                        <input class="form-control" type="text" :value="auth.user.name" readonly>
                                    </div>
                                    <div class="col-md-6 float-end">
                                        <label class="fw-bold mb-2">Customer</label>
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div class="flex-grow-1">
                                                <VueMultiselect v-if="!manualCustomer" v-model="customer_id" label="name" track-by="name" :options="customers"></VueMultiselect>
                                                <input v-else type="text" class="form-control" v-model="manualCustomerName" placeholder="Nama Customer (manual)">
                                            </div>
                                            <div class="form-check form-switch me-2">
                                                <input class="form-check-input" type="checkbox" id="manualCustomerSwitch" v-model="manualCustomer">
                                                <label class="form-check-label" for="manualCustomerSwitch"></label>
                                            </div>
                                        </div>
                                    </div>
                                 </div>
                                 <hr>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr style="background-color: #e6e6e7;">
                                            <th scope="col">Action</th>
                                            <th scope="col">Product Name</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Qty</th>
                                            <th scope="col">Sub Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="cart in carts" :key="cart.id">
                                            <td class="text-center">
                                                <button @click.prevent="destroyCart(cart.id)" class="btn btn-danger btn-sm rounded-pill"><i class="fa fa-trash"></i></button>
                                            </td>
                                            <td>{{ cart.product.title }}</td>
                                            <td>Rp. {{ formatPrice(cart.product.sell_price) }}</td>
                                            <td class="text-center">{{ cart.qty }}</td>
                                            <td class="text-end">Rp. {{ formatPrice(cart.price) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold" style="background-color: #e6e6e7;">TOTAL</td>
                                            <td class="text-end fw-bold" style="background-color: #e6e6e7;">Rp. {{ formatPrice(carts_total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <hr>
                                <div class="d-flex align-items-end flex-column bd-highlight mb-3">
                                    <div class="mt-auto bd-highlight">
                                        <label>Discount (Rp.)</label>
                                        <input type="number" v-model="discount" @keyup="setDiscount" class="form-control" placeholder="Discount (Rp.)">
                                    </div>
                                    <div class="bd-highlight mt-4">
                                        <label>Pay (Rp.)</label>
                                        <input type="number" v-model="cash" @keyup="setChange" class="form-control" placeholder="Pay (Rp.)">
                                    </div>
                                </div>
                                <div class="text-end mt-4">
                                    <button class="btn btn-warning btn-md border-0 shadow text-uppercase me-2">Cancel</button>
                                    <button @click.prevent="storeTransaction" class="btn btn-purple btn-md border-0 shadow text-uppercase" :disabled="cash < grandTotal || grandTotal == 0">Pay Order & Print</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<script>

    //import layout
    import LayoutApp from '../../../Layouts/App.vue';

    //import Heade from Inertia
    import { Head, router } from '@inertiajs/vue3';

    //import VueMultiselect
    import VueMultiselect from 'vue-multiselect';
    import 'vue-multiselect/dist/vue-multiselect.css';

    //import ref form vue
    import { ref } from 'vue';

    //import axios
    import axios from 'axios';

    //import sweet alert2
    import Swal from 'sweetalert2';

    export default {
        //layout
        layout: LayoutApp,

        //register components
        components: {
            Head,
            VueMultiselect
        },

        //props
        props: {
            auth: Object,
            customers: Array,
            carts_total: Number,
            session: Object,
            carts: Array
        },

        //composition API
        setup(props) {

            //define state
            const barcode = ref('');
            const product = ref({});
            const qty = ref(1);
            const suggestions = ref([]);
            const suggestionsVisible = ref(false);
            let suggestionTimer = null;

            //method to fetch suggestions by name/barcode
            const onBarcodeInput = () => {
                const q = (barcode.value || '').trim();

                // reset product when user is typing a new query
                product.value = {};

                if (suggestionTimer) clearTimeout(suggestionTimer);

                if (q.length < 2) {
                    suggestions.value = [];
                    suggestionsVisible.value = false;
                    return;
                }

                suggestionTimer = setTimeout(() => {
                    axios.post('/apps/transactions/searchProducts', { q })
                        .then(res => {
                            if (res.data && res.data.success) {
                                suggestions.value = res.data.data;
                                suggestionsVisible.value = suggestions.value.length > 0;
                            } else {
                                suggestions.value = [];
                                suggestionsVisible.value = false;
                            }
                        })
                        .catch(() => {
                            suggestions.value = [];
                            suggestionsVisible.value = false;
                        });
                }, 250);
            };

            const selectSuggestion = (item) => {
                product.value = item;
                barcode.value = item.barcode || item.title;
                suggestions.value = [];
                suggestionsVisible.value = false;
            };

            //metho "searchProduct" (exact match by barcode on Enter)
            const searchProduct = () => {

                //fetch with axios
                axios.post('/apps/transactions/searchProduct', {

                    //send data "barcode"
                    barcode: barcode.value

                }).then(response => {
                    if(response.data.success) {

                        //assign response to state "product"
                        product.value = response.data.data;
                    } else {

                        //set state "product" to empty object
                        product.value = {};
                    }

                    // hide suggestions when pressing enter
                    suggestions.value = [];
                    suggestionsVisible.value = false;
                });
            }

            //method "clearSearch"
            const clearSearch = () => {

                //set state "product" to empty object
                product.value = {};

                //set state "barcode" to empty string
                barcode.value = '';

                //clear suggestions
                suggestions.value = [];
                suggestionsVisible.value = false;
            }

            //define state grandTotal
            const grandTotal = ref(props.carts_total);

            //method add to cart
            const addToCart = () => {


                //send data to server
                router.post('/apps/transactions/addToCart', {

                    //data
                    product_id: product.value.id,
                    qty: qty.value,
                    sell_price: product.value.sell_price,

                }, {
                    onSuccess: () => {

                        //call method "clearSaerch"
                        clearSearch();

                        //set qty to "1"
                        qty.value = 1;

                        //update state "grandTotal"
                        grandTotal.value = props.carts_total;

                        //set cash to "0"
                        cash.value = 0;

                        //set change to "0"
                        change.value = 0;

                        //ensure suggestions hidden
                        suggestions.value = [];
                        suggestionsVisible.value = false;
                    },
                });


            }

            //method "destroyCart"
            const destroyCart = (cart_id) => {
                router.post('/apps/transactions/destroyCart', {
                    cart_id: cart_id
                }, {
                    onSuccess: () => {

                        //update state "grandTotal"
                        grandTotal.value = props.carts_total;

                        //set cash to "0"
                        cash.value = 0;

                        //set change to "0"
                        change.value = 0;
                    },
                })
            }

            //define state "cash", "change" dan "discount"
            const cash      = ref(0);
            const change    = ref(0);
            const discount  = ref(0);

            //method "setDiscount"
            const setDiscount = () => {

                //set grandTotal
                grandTotal.value = props.carts_total - discount.value;

                //set cash to "0"
                cash.value = 0;

                //set change to "0"
                change.value = 0;
            }

            //method "setChange"
            const setChange = () => {

                //set change
                change.value = cash.value - grandTotal.value;
            }

            //define state "customer_id"
            const customer_id = ref('');
            const manualCustomer = ref(false);
            const manualCustomerName = ref('');

            //method "storeTransaction"
            const storeTransaction = () => {

                // validasi sederhana: jika manual aktif tapi nama kosong
                if (manualCustomer.value && (!manualCustomerName.value || manualCustomerName.value.trim() === '')) {
                    Swal.fire({
                        title: 'Perhatian',
                        text: 'Nama customer belum diisi.',
                        icon: 'warning'
                    });
                    return;
                }

                //HTTP request
                axios.post('/apps/transactions/store', {

                    //send data to server
                    customer_id: customer_id.value ? customer_id.value.id : '',
                    manual_customer_name: manualCustomer.value ? manualCustomerName.value.trim() : '',
                    discount: discount.value,
                    grand_total: grandTotal.value,
                    cash: cash.value,
                    change: change.value
                })
                .then(response => {

                    //call method "clearSaerch"
                    clearSearch();

                    //reset customer input
                    customer_id.value = '';
                    manualCustomer.value = false;
                    manualCustomerName.value = '';

                    //set qty to "1"
                    qty.value = 1;

                    //set grandTotal
                    grandTotal.value = props.carts_total;

                    //set cash to "0"
                    cash.value = 0;

                    //set change to "0"
                    change.value = 0;

                    //show success alert
                    Swal.fire({
                        title: 'Success!',
                        text: 'Transaction Successfully.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000
                    })
                    .then(() => {

                        setTimeout(() => {

                            //print
                            window.open(`/apps/transactions/print?invoice=${response.data.data.invoice}`, '_blank');

                            //reload page
                            location.reload();

                        }, 50);

                    })
                })

            }

            // Reprint receipt by invoice
            const reprintInvoice = ref('');
            const reprintReceipt = () => {
                // normalisasi input: hapus tanda kutip agar pencarian nama tidak gagal
                const inv = (reprintInvoice.value || '').replace(/["']/g, '').trim();
                if (!inv) {
                    Swal.fire({
                        title: 'Perhatian',
                        text: 'Nama customer belum diisi.',
                        icon: 'warning'
                    });
                    return;
                }

                // Jika input sudah berbentuk nomor invoice, langsung cetak
                const isInvoiceFormat = /^TRX-/i.test(inv);
                if (isInvoiceFormat) {
                    window.open(`/apps/transactions/print?invoice=${encodeURIComponent(inv)}`, '_blank');
                    return;
                }

                // Fallback: jika user mengetik nama customer, cari invoice terkait (ambil yang terbaru)
                axios.get('/apps/transactions/searchInvoices', { params: { q: inv } })
                    .then(res => {
                        if (res.data && res.data.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
                            const invoiceToPrint = res.data.data[0].invoice; // ambil yang terbaru (query sudah DESC)
                            reprintInvoice.value = invoiceToPrint;
                            window.open(`/apps/transactions/print?invoice=${encodeURIComponent(invoiceToPrint)}`, '_blank');
                        } else {
                            Swal.fire({
                                title: 'Tidak ditemukan',
                                text: 'Tidak ada transaksi untuk nama tersebut. Coba ketik sebagian nama atau pilih dari saran.',
                                icon: 'info'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            title: 'Gagal',
                            text: 'Tidak dapat mencari transaksi. Coba lagi.',
                            icon: 'error'
                        });
                    });
            };

            // Autocomplete invoice suggestions
            const invoiceSuggestions = ref([]);
            const invoiceSuggestionsVisible = ref(false);
            let invoiceSuggestionTimer = null;

            const onInvoiceInput = () => {
                // normalisasi input: hapus tanda kutip
                const q = (reprintInvoice.value || '').replace(/["']/g, '').trim();

                if (invoiceSuggestionTimer) clearTimeout(invoiceSuggestionTimer);

                if (q.length < 2) {
                    invoiceSuggestions.value = [];
                    invoiceSuggestionsVisible.value = false;
                    return;
                }

                invoiceSuggestionTimer = setTimeout(() => {
                    axios.get('/apps/transactions/searchInvoices', { params: { q } })
                        .then(res => {
                            if (res.data && res.data.success) {
                                invoiceSuggestions.value = res.data.data;
                                invoiceSuggestionsVisible.value = invoiceSuggestions.value.length > 0;
                            } else {
                                invoiceSuggestions.value = [];
                                invoiceSuggestionsVisible.value = false;
                            }
                        })
                        .catch(() => {
                            invoiceSuggestions.value = [];
                            invoiceSuggestionsVisible.value = false;
                        });
                }, 250);
            };

            const selectInvoiceSuggestion = (item) => {
                reprintInvoice.value = item.invoice;
                invoiceSuggestions.value = [];
                invoiceSuggestionsVisible.value = false;
            };

            // Daily invoices by date
            const reprintDate = ref('');
            const dailyInvoices = ref([]);
            const selectedInvoice = ref('');

            const loadDailyInvoices = () => {
                const date = (reprintDate.value || '').trim();
                axios.get('/apps/transactions/searchInvoices', { params: { date } })
                    .then(res => {
                        if (res.data && res.data.success) {
                            dailyInvoices.value = res.data.data;
                        } else {
                            dailyInvoices.value = [];
                        }
                    })
                    .catch(() => {
                        dailyInvoices.value = [];
                    });
            };

            const applySelectedInvoice = () => {
                if (selectedInvoice.value) {
                    reprintInvoice.value = selectedInvoice.value;
                }
            };

            return {
                barcode,
                product,
                searchProduct,
                clearSearch,
                qty,
                grandTotal,
                addToCart,
                destroyCart,
                cash,
                change,
                discount,
                setDiscount,
                setChange,
                customer_id,
                manualCustomer,
                manualCustomerName,
                storeTransaction,
                // new
                suggestions,
                suggestionsVisible,
                onBarcodeInput,
                selectSuggestion,
                // reprint
                reprintInvoice,
                reprintReceipt,
                // invoice autocomplete
                invoiceSuggestions,
                invoiceSuggestionsVisible,
                onInvoiceInput,
                selectInvoiceSuggestion,
                // daily
                reprintDate,
                dailyInvoices,
                selectedInvoice,
                loadDailyInvoices,
                applySelectedInvoice
            }

        }
    }
</script>

<style>

</style>
