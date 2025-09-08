<template>
    <Head>
        <title>Stock Management - Aplikasi Kasir</title>
    </Head>
    <main class="c-main">
        <div class="container-fluid">
            <div class="fade-in">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card border-0 rounded-3 shadow border-top-purple">
                            <div class="card-header">
                                <span class="font-weight-bold"><i class="fa fa-box"></i> STOCK MANAGEMENT</span>
                            </div>
                            <div class="card-body">
                                <!-- Search -->
                                <form @submit.prevent="handleSearch" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" v-model="search" placeholder="search by product title...">
                                        <button class="btn btn-primary input-group-text" type="submit">
                                            <i class="fa fa-search me-2"></i> SEARCH
                                        </button>
                                    </div>
                                </form>

                                <!-- Filter by Date Range -->
                                <form @submit.prevent="filter" class="mb-3">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">START DATE</label>
                                                <input type="date" v-model="start_date" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">END DATE</label>
                                                <input type="date" v-model="end_date" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold text-white">*</label>
                                                <button class="btn btn-md btn-purple border-0 shadow w-100">
                                                    <i class="fa fa-filter"></i> FILTER
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- Export Buttons (only show AFTER filter applied) -->
                                <!-- use hasFiltered (props-based) so user must click FILTER first -->
                                <div v-if="hasFiltered" class="text-end mb-3">
                                    <a :href="`/apps/stocks/export?start_date=${propsStart}&end_date=${propsEnd}&q=${search || ''}&t=${Date.now()}`" target="_blank" class="btn btn-success btn-md border-0 shadow me-3">
                                        <i class="fa fa-file-excel"></i> EXCEL
                                    </a>
                                    <a :href="`/apps/stocks/pdf?start_date=${propsStart}&end_date=${propsEnd}&q=${search || ''}&t=${Date.now()}`" target="_blank" class="btn btn-secondary btn-md border-0 shadow">
                                        <i class="fa fa-file-pdf"></i> PDF
                                    </a>
                                </div>

                                <!-- Table -->
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">Nama Barang</th>
                                            <th scope="col">Kategori</th>
                                            <th scope="col">Stock</th>
                                            <th scope="col">USER</th>
                                            <th scope="col">Keterangan</th>
                                            <th scope="col">Tanggal Update</th>
                                            <!-- Actions column restored -->
                                            <th scope="col" style="width:18%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(product, index) in (products?.data || [])" :key="index">
                                            <td>{{ product.title }}</td>
                                            <td>{{ product.category ? product.category.name : '-' }}</td>
                                            <td class="text-center">{{ product.stock }}</td>
                                            <td>{{ product.latest_stock_entry && product.latest_stock_entry.user ? product.latest_stock_entry.user.name : '-' }}</td>
                                            <td>{{ product.latest_stock_entry ? (product.latest_stock_entry.note || '-') : '-' }}</td>
                                            <td>{{ product.latest_stock_entry ? product.latest_stock_entry.created_at : '-' }}</td>
                                            <!-- Row actions -->
                                            <td class="text-center">
                                                <button v-if="hasAnyPermission(['stocks.update'])" @click.prevent="addStock(product)" class="btn btn-primary btn-sm">
                                                    <i class="fa fa-plus-circle me-1"></i> NEW
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="!products || (products.data && products.data.length === 0)">
                                            <td colspan="7" class="text-center">No data</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Pagination -->
                                <Pagination v-if="products && products.links" :links="products.links" align="end" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<script>
    import LayoutApp from '../../../Layouts/App.vue';
    import Pagination from '../../../Components/Pagination.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import Swal from 'sweetalert2';

    export default {
        layout: LayoutApp,
        components: { Head, Link, Pagination },
        props: { products: Object, start_date: String, end_date: String },
        setup(props) {
            const params = new URL(document.location).searchParams;
            const search = ref(params.get('q') || '');
            // input controls can start from URL or props
            const start_date = ref(params.get('start_date') || props.start_date || '');
            const end_date = ref(params.get('end_date') || props.end_date || '');

            // props-based filter state (only true after server-side filter applied)
            const propsStart = computed(() => props.start_date || '');
            const propsEnd = computed(() => props.end_date || '');
            const hasFiltered = computed(() => !!(propsStart.value && propsEnd.value));

            const handleSearch = () => {
                // keep using filter endpoint only if filter is already applied on server (props)
                if (hasFiltered.value) {
                    router.get('/apps/stocks/filter', {
                        q: search.value,
                        start_date: propsStart.value,
                        end_date: propsEnd.value,
                    });
                } else {
                    router.get('/apps/stocks', {
                        q: search.value,
                    });
                }
            };

            const filter = () => {
                router.get('/apps/stocks/filter', {
                    start_date: start_date.value,
                    end_date: end_date.value,
                    q: search.value,
                });
            };

            // add stock via swal prompt
            const addStock = (product) => {
                Swal.fire({
                    title: `Tambah Stok - ${product.title}`,
                    html: `
                        <div class='mb-3 text-start'>
                            <label class='form-label'>Quantity <span class='text-danger'>*</span></label>
                            <input id='swal-qty' type='number' min='1' class='form-control' placeholder='masukkan jumlah'>
                        </div>
                        <div class='text-start'>
                            <label class='form-label'>Catatan (opsional)</label>
                            <textarea id='swal-note' rows='3' class='form-control' placeholder='keterangan'></textarea>
                        </div>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    preConfirm: () => {
                        const qty = parseInt(document.getElementById('swal-qty').value, 10);
                        const note = document.getElementById('swal-note').value || '';
                        if (!qty || qty < 1) {
                            Swal.showValidationMessage('Quantity minimal 1');
                            return false;
                        }
                        return { quantity: qty, note };
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        router.post(`/apps/stocks/${product.id}/add`, {
                            quantity: result.value.quantity,
                            note: result.value.note,
                        }, {
                            onSuccess: () => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: 'Stok berhasil ditambahkan',
                                    timer: 1500,
                                    showConfirmButton: false,
                                });
                            },
                        });
                    }
                });
            };

            return { search, start_date, end_date, handleSearch, filter, addStock, hasFiltered, propsStart: computed(() => propsStart.value), propsEnd: computed(() => propsEnd.value) };
        }
    };
</script>

<style></style>