<div class="col-lg-2" style="position: fixed; right: 0">
    <div class="border-left border-lighter ml-2" id="headings">
        
    </div>
</div>

@section('footer')

<script>

var headers = []

function walk (nodes) {
    nodes.forEach((node) => {
        var sub = Array.from(node.childNodes)
        if (sub.length) {
            walk(sub)
        }
        if (/h[1-6]/i.test(node.tagName)) {
            headers.push({
                id: node.getAttribute('id'),
                level: parseInt(node.tagName.replace('H', '')),
                title: node.innerText
            })
        }
    })
}

walk(Array.from(document.body.childNodes))

var link = (header) =>
'<li><a href="#' + header.id + '" class="text-gray">' + header.title + '</a></li>'

var html = '<ul class="pl-3" style="list-style: none">'

headers.forEach((header, index) => {

    if(index)
    {
        var prev = headers[index - 1];

        if(header.level === prev.level || header.level === 1)
        {
            html += link(header);
        }
        for (let i = 1; i < index + 1; i++) 
        {
            if (header.level === prev.level + i)
            {
                html += '<ul class="pl-3" style="list-style: none">'.repeat(i) + link(header);
            }
            else if(header.level === prev.level - i)
            {
                html += '</ul>'.repeat(i) + link(header);
            }
        }
    }
})

html += '</ul>';

document.getElementById('headings').innerHTML = html;

</script>

@endsection