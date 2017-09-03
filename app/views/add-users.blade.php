@include('layout.header')


<h1>Dodavanje korisnika</h1><hr>

<h3>Dodano je <strong>{{ $number_of_seeds }}</strong> korisnika.</h3>
<h3>Skripta se izvodila: <strong>{{ $time_calculations }}</strong> sek.</h3>
<h3 id="time-loader-holder">Stranica se učitala za: <strong><span id="time-loader"></span></strong> sek.</h3>

<section id="content-output">
    <div class="table-responsive">
        <table class="table container-table" id="responsive-data-table">
            <thead>
            <tr>
                <td>Ime</td>
                <td>Prezime</td>
                <td>Korisničko ime</td>
                <td>E-mail adresa</td>
                <td>Lozinka (hash)</td>
                <td>Ime fakulteta/učilište</td>
            </tr>
            </thead>
            <tbody>
            @foreach($seeds_storage as $seed)
                <tr>
                    <td>{{ $seed['first_name'] }}</td>
                    <td>{{ $seed['last_name'] }}</td>
                    <td>{{ $seed['username'] }}</td>
                    <td>{{ $seed['email'] }}</td>
                    <td>{{ substr($seed['password'], -10); }}.....</td>
                    <td>{{ $universities[$seed['university_id'] - 1]->university }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>


@include('layout.footer')